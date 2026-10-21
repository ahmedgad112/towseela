<?php

namespace App\Services;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PricingService
{
    /**
     * @param  list<int>  $optionValueIds
     * @return array{unit_price: string, option_labels: list<string>}
     */
    public function unitPrice(Product $product, array $optionValueIds = []): array
    {
        $unitPrice = Money::of($product->price);
        $labels = [];

        if ($optionValueIds === []) {
            return ['unit_price' => $unitPrice, 'option_labels' => $labels];
        }

        $values = ProductOptionValue::query()
            ->with('option')
            ->whereIn('id', $optionValueIds)
            ->get();

        foreach ($values as $value) {
            if ($value->option?->product_id !== $product->id) {
                throw ValidationException::withMessages([
                    'options' => __('الخيارات المحددة لا تنتمي لهذا المنتج.'),
                ]);
            }

            $unitPrice = Money::add($unitPrice, $value->price_delta);
            $labels[] = $value->name_ar;
        }

        return ['unit_price' => $unitPrice, 'option_labels' => $labels];
    }

    public function deliveryFee(?DeliveryZone $zone, string $subtotal): string
    {
        if (! $zone || ! $zone->is_active) {
            throw ValidationException::withMessages([
                'address_id' => __('عنوان التوصيل خارج نطاق التغطية الحالي.'),
            ]);
        }

        $rule = $zone->feeRules
            ->where('is_active', true)
            ->filter(fn ($feeRule) => Money::greaterOrEqual($subtotal, $feeRule->min_order))
            ->sortByDesc(fn ($feeRule) => (float) $feeRule->min_order)
            ->first();

        if (! $rule) {
            throw ValidationException::withMessages([
                'address_id' => __('لا توجد رسوم توصيل معرفة لهذه المنطقة.'),
            ]);
        }

        return Money::of($rule->fee);
    }

    public function discount(?Coupon $coupon, string $subtotal): string
    {
        if (! $coupon) {
            return Money::of(0);
        }

        if (! $coupon->isCurrentlyValid()) {
            throw ValidationException::withMessages([
                'coupon' => __('الكوبون غير صالح أو منتهي.'),
            ]);
        }

        if (! Money::greaterOrEqual($subtotal, $coupon->min_order)) {
            throw ValidationException::withMessages([
                'coupon' => __('لم يتم بلوغ الحد الأدنى لاستخدام هذا الكوبون.'),
            ]);
        }

        $discount = $coupon->type === CouponType::Percent
            ? Money::percent($subtotal, $coupon->value)
            : Money::of($coupon->value);

        if ($coupon->max_discount !== null) {
            $discount = Money::min($discount, $coupon->max_discount);
        }

        return Money::min($discount, $subtotal);
    }

    /**
     * @param  Collection<int, array{unit_price: string, quantity: int}>  $lines
     * @return array{subtotal: string, discount: string, delivery_fee: string, grand_total: string}
     */
    public function totals(Collection $lines, ?DeliveryZone $zone, ?Coupon $coupon): array
    {
        $subtotal = Money::of(0);

        foreach ($lines as $line) {
            $subtotal = Money::add($subtotal, Money::multiply($line['unit_price'], $line['quantity']));
        }

        $discount = $this->discount($coupon, $subtotal);
        $afterDiscount = Money::subtract($subtotal, $discount);
        $deliveryFee = $this->deliveryFee($zone, $afterDiscount);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'delivery_fee' => $deliveryFee,
            'grand_total' => Money::add($afterDiscount, $deliveryFee),
        ];
    }
}
