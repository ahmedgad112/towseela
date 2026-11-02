<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CouponUsage;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\Money;
use App\Support\OrderRealtime;
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
            if (Order::query()->where('checkout_token', $checkoutToken)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'checkout_token' => __('تم إنشاء هذا الطلب مسبقاً.'),
                ]);
            }

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

            $totals = $this->pricing->totals(
                $lines->map(fn (array $line) => [
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                ]),
                $address->deliveryZone,
                $cart->coupon,
            );

            if (! Money::greaterOrEqual($totals['subtotal'], $business->min_order_amount)) {
                throw ValidationException::withMessages([
                    'cart' => __('لم يتم بلوغ الحد الأدنى للطلب من هذا المتجر.'),
                ]);
            }

            $order = Order::query()->create([
                'number' => $this->nextNumber(),
                'user_id' => $user->id,
                'business_id' => $business->id,
                'business_branch_id' => $business->branches->firstWhere('is_active', true)?->id,
                'delivery_zone_id' => $address->delivery_zone_id,
                'coupon_id' => $cart->coupon_id,
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
                'user_id' => $user->id,
            ]);

            $order->payment()->create([
                'method' => PaymentMethod::CashOnDelivery,
                'status' => PaymentStatus::Pending,
                'amount' => $totals['grand_total'],
            ]);

            if ($cart->coupon) {
                CouponUsage::query()->create([
                    'coupon_id' => $cart->coupon->id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                ]);

                $cart->coupon->increment('used_count');
            }

            $this->carts->clear($cart);
            $this->audit->record(AuditAction::OrderStatusChanged, $order, $user, [
                'to' => OrderStatus::PendingConfirmation->value,
            ]);

            return $order->load(['items', 'business', 'payment']);
        });

        $this->realtime->orderCreated($order);

        return $order;
    }

    private function nextNumber(): string
    {
        do {
            $number = 'TW-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
