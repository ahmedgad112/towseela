<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use App\Support\PlatformSettings;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        private PricingService $pricing,
        private PlatformSettings $platformSettings,
    ) {}

    public function forUser(User $user): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * @param  list<int>  $optionValueIds
     */
    public function addItem(User $user, Product $product, int $quantity, array $optionValueIds = [], ?string $notes = null): CartItem
    {
        if (! $product->is_active || ! $product->is_available) {
            throw ValidationException::withMessages([
                'product_id' => __('هذا المنتج غير متاح حالياً.'),
            ]);
        }

        $cart = $this->forUser($user);

        if ($cart->business_id && $cart->business_id !== $product->business_id && ! $this->platformSettings->operations()['allow_mixed_business_cart']) {
            throw ValidationException::withMessages([
                'product_id' => __('لا يمكن خلط منتجات من متاجر مختلفة في سلة واحدة. أفرغ السلة أولاً.'),
            ]);
        }

        $this->pricing->unitPrice($product, $optionValueIds);

        $cart->forceFill(['business_id' => $product->business_id])->save();

        $existing = $cart->items()
            ->where('product_id', $product->id)
            ->get()
            ->first(function (CartItem $item) use ($optionValueIds, $notes): bool {
                $current = collect($item->options ?? [])->sort()->values()->all();
                $incoming = collect($optionValueIds)->sort()->values()->all();

                return $current === $incoming && $item->notes === $notes;
            });

        if ($existing) {
            $existing->update(['quantity' => $existing->quantity + $quantity]);

            return $existing->refresh();
        }

        return $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'options' => $optionValueIds,
            'notes' => $notes,
        ]);
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity < 1) {
            $item->delete();
            $this->resetEmptyCart($item->cart);

            return;
        }

        $item->update(['quantity' => $quantity]);
    }

    public function removeItem(CartItem $item): void
    {
        $cart = $item->cart;
        $item->delete();
        $this->resetEmptyCart($cart);
    }

    public function applyCoupon(Cart $cart, string $code): void
    {
        $coupon = Coupon::query()->where('code', $code)->first();

        if (! $coupon) {
            throw ValidationException::withMessages([
                'coupon' => __('كود الخصم غير موجود.'),
            ]);
        }

        $summary = $this->summary($cart);
        $this->pricing->discount($coupon, $summary['subtotal']);

        $cart->update(['coupon_id' => $coupon->id]);
    }

    public function clearCoupon(Cart $cart): void
    {
        $cart->update(['coupon_id' => null]);
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update([
            'business_id' => null,
            'coupon_id' => null,
            'notes' => null,
        ]);
    }

    /**
     * Lightweight cart payload for headers and quick-add UI (no pricing).
     *
     * @return array{items: list<array<string, mixed>>, count: int}
     */
    public function snapshot(Cart $cart): array
    {
        $cart->loadMissing('items');

        $items = $cart->items
            ->map(fn (CartItem $item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'options' => array_values($item->options ?? []),
                'notes' => $item->notes,
            ])
            ->values()
            ->all();

        return [
            'items' => $items,
            'count' => (int) $cart->items->sum('quantity'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Cart $cart, ?CustomerAddress $address = null): array
    {
        $cart->loadMissing(['items.product', 'business', 'coupon']);

        $lines = $cart->items->map(function (CartItem $item) {
            $priced = $this->pricing->unitPrice($item->product, $item->options ?? []);

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name_ar,
                'quantity' => $item->quantity,
                'unit_price' => $priced['unit_price'],
                'total' => Money::multiply($priced['unit_price'], $item->quantity),
                'options' => $priced['option_labels'],
                'notes' => $item->notes,
            ];
        });

        $zone = $address?->deliveryZone;
        $coupon = $cart->coupon;

        try {
            $totals = $lines->isEmpty()
                ? ['subtotal' => '0.00', 'discount' => '0.00', 'delivery_fee' => '0.00', 'grand_total' => '0.00']
                : $this->pricing->totals(
                    $lines->map(fn (array $line) => [
                        'unit_price' => $line['unit_price'],
                        'quantity' => $line['quantity'],
                    ]),
                    $zone,
                    $coupon,
                );
        } catch (ValidationException $exception) {
            $subtotal = $lines->reduce(
                fn (string $carry, array $line) => Money::add($carry, $line['total']),
                '0.00',
            );

            $totals = [
                'subtotal' => $subtotal,
                'discount' => '0.00',
                'delivery_fee' => null,
                'grand_total' => $subtotal,
                'pricing_error' => collect($exception->errors())->flatten()->first(),
            ];
        }

        return [
            'items' => $lines->values(),
            'count' => $cart->items->sum('quantity'),
            'business' => $cart->business,
            'coupon' => $coupon,
            ...$totals,
        ];
    }

    private function resetEmptyCart(Cart $cart): void
    {
        if ($cart->items()->exists()) {
            return;
        }

        $cart->update([
            'business_id' => null,
            'coupon_id' => null,
        ]);
    }
}
