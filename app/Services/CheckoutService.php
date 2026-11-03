<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Business;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\Money;
use App\Support\OrderRealtime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private CartService $carts,
        private PricingService $pricing,
        private AuditLogger $audit,
        private OrderRealtime $realtime,
    ) {}

    public function place(User $user, CustomerAddress $address, string $checkoutToken, ?string $notes = null): Order
    {
        if ($address->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'address_id' => __('عنوان التوصيل غير صالح.'),
            ]);
        }

        $order = DB::transaction(function () use ($user, $address, $checkoutToken, $notes) {
            $this->assertFreshCheckoutToken($checkoutToken);

            $cart = Cart::query()
                ->whereBelongsTo($user)
                ->lockForUpdate()
                ->first();

            if (! $cart || $cart->items()->doesntExist()) {
                throw ValidationException::withMessages([
                    'cart' => __('السلة فارغة.'),
                ]);
            }

            $cart->load(['items.product.options.values', 'business.hours', 'coupon', 'business.branches']);
            $address->load('deliveryZone.feeRules');

            $business = $cart->business;

            if (! $business?->is_active || ! $business->accepts_orders) {
                throw ValidationException::withMessages([
                    'cart' => __('هذا المتجر لا يستقبل طلبات حالياً.'),
                ]);
            }

            $lines = $cart->items->map(function ($item) {
                if (! $item->product?->is_active || ! $item->product->is_available) {
                    throw ValidationException::withMessages([
                        'cart' => __('أحد المنتجات لم يعد متاحاً.'),
                    ]);
                }

                $priced = $this->pricing->unitPrice($item->product, $item->options ?? []);

                return [
                    'product' => $item->product,
                    'quantity' => $item->quantity,
                    'unit_price' => $priced['unit_price'],
                    'total' => Money::multiply($priced['unit_price'], $item->quantity),
                    'options' => $priced['option_labels'],
                    'notes' => $item->notes,
                ];
            });

            $order = $this->persistOrder(
                customer: $user,
                actor: $user,
                address: $address,
                business: $business,
                lines: $lines,
                checkoutToken: $checkoutToken,
                notes: $notes,
                coupon: $cart->coupon,
            );

            $this->carts->clear($cart);

            return $order;
        });

        $this->realtime->orderCreated($order);

        return $order;
    }

    /**
     * @param  list<array{product_id: int, quantity: int, options?: list<int>, notes?: string|null}>  $items
     */
    public function placeAssisted(
        User $customer,
        CustomerAddress $address,
        array $items,
        User $actor,
        string $checkoutToken,
        ?string $notes = null,
    ): Order {
        if ($address->user_id !== $customer->id) {
            throw ValidationException::withMessages([
                'address_id' => __('عنوان التوصيل غير صالح.'),
            ]);
        }

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => __('أضف منتجاً واحداً على الأقل.'),
            ]);
        }

        $order = DB::transaction(function () use ($customer, $address, $items, $actor, $checkoutToken, $notes) {
            $this->assertFreshCheckoutToken($checkoutToken);

            $address->load('deliveryZone.feeRules');

            $productIds = collect($items)->pluck('product_id')->unique()->values()->all();
            $products = Product::query()
                ->with(['options.values', 'business.branches'])
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw ValidationException::withMessages([
                    'items' => __('أحد المنتجات غير موجود.'),
                ]);
            }

            $businessIds = $products->pluck('business_id')->unique();

            if ($businessIds->count() !== 1) {
                throw ValidationException::withMessages([
                    'items' => __('يجب أن تكون كل المنتجات من نفس المتجر.'),
                ]);
            }

            /** @var Business $business */
            $business = $products->first()->business;

            if (! $business->is_active || ! $business->accepts_orders) {
                throw ValidationException::withMessages([
                    'items' => __('هذا المتجر لا يستقبل طلبات حالياً.'),
                ]);
            }

            $lines = collect($items)->map(function (array $item) use ($products) {
                /** @var Product $product */
                $product = $products->get($item['product_id']);

                if (! $product->is_active || ! $product->is_available) {
                    throw ValidationException::withMessages([
                        'items' => __('أحد المنتجات لم يعد متاحاً.'),
                    ]);
                }

                $options = array_values($item['options'] ?? []);
                $priced = $this->pricing->unitPrice($product, $options);

                return [
                    'product' => $product,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $priced['unit_price'],
                    'total' => Money::multiply($priced['unit_price'], (int) $item['quantity']),
                    'options' => $priced['option_labels'],
                    'notes' => $item['notes'] ?? null,
                ];
            });

            return $this->persistOrder(
                customer: $customer,
                actor: $actor,
                address: $address,
                business: $business,
                lines: $lines,
                checkoutToken: $checkoutToken,
                notes: $notes,
            );
        });

        $this->realtime->orderCreated($order);

        return $order;
    }

    /**
     * @param  Collection<int, array{product: Product, quantity: int, unit_price: string, total: string, options: list<string>, notes: string|null}>  $lines
     */
    private function persistOrder(
        User $customer,
        User $actor,
        CustomerAddress $address,
        Business $business,
        Collection $lines,
        string $checkoutToken,
        ?string $notes = null,
        ?Coupon $coupon = null,
    ): Order {
        $business->loadMissing('branches');

        $totals = $this->pricing->totals(
            $lines->map(fn (array $line) => [
                'unit_price' => $line['unit_price'],
                'quantity' => $line['quantity'],
            ]),
            $address->deliveryZone,
            $coupon,
        );

        if (! Money::greaterOrEqual($totals['subtotal'], $business->min_order_amount)) {
            throw ValidationException::withMessages([
                'items' => __('لم يتم بلوغ الحد الأدنى للطلب من هذا المتجر.'),
            ]);
        }

        $order = Order::query()->create([
            'number' => $this->nextNumber(),
            'user_id' => $customer->id,
            'business_id' => $business->id,
            'business_branch_id' => $business->branches->firstWhere('is_active', true)?->id,
            'delivery_zone_id' => $address->delivery_zone_id,
            'coupon_id' => $coupon?->id,
            'status' => OrderStatus::PendingConfirmation,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'address_snapshot' => $address->toSnapshot(),
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'delivery_fee' => $totals['delivery_fee'],
            'grand_total' => $totals['grand_total'],
            'customer_notes' => $notes,
            'checkout_token' => $checkoutToken,
        ]);

        foreach ($lines as $line) {
            $order->items()->create([
                'product_id' => $line['product']->id,
                'name' => $line['product']->name_ar,
                'unit_price' => $line['unit_price'],
                'quantity' => $line['quantity'],
                'total' => $line['total'],
                'options' => $line['options'],
                'notes' => $line['notes'],
            ]);
        }

        $order->statusHistories()->create([
            'from_status' => OrderStatus::New,
            'to_status' => OrderStatus::PendingConfirmation,
            'user_id' => $actor->id,
        ]);

        $order->payment()->create([
            'method' => PaymentMethod::CashOnDelivery,
            'status' => PaymentStatus::Pending,
            'amount' => $totals['grand_total'],
        ]);

        if ($coupon) {
            CouponUsage::query()->create([
                'coupon_id' => $coupon->id,
                'user_id' => $customer->id,
                'order_id' => $order->id,
            ]);

            $coupon->increment('used_count');
        }

        $this->audit->record(AuditAction::OrderStatusChanged, $order, $actor, [
            'to' => OrderStatus::PendingConfirmation->value,
            'source' => $actor->is($customer) ? 'customer' : 'assisted',
        ]);

        return $order->load(['items', 'business', 'payment', 'customer']);
    }

    private function assertFreshCheckoutToken(string $checkoutToken): void
    {
        if (Order::query()->where('checkout_token', $checkoutToken)->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([
                'checkout_token' => __('تم إنشاء هذا الطلب مسبقاً.'),
            ]);
        }
    }

    private function nextNumber(): string
    {
        do {
            $number = 'TW-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
