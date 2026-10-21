<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Business;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'TW-'.Str::upper(Str::random(8)),
            'user_id' => User::factory(),
            'business_id' => Business::factory(),
            'status' => OrderStatus::PendingConfirmation,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'address_snapshot' => [
                'line1' => 'شارع تجريبي',
                'city' => 'القاهرة',
            ],
            'subtotal' => 100,
            'discount' => 0,
            'delivery_fee' => 15,
            'grand_total' => 115,
            'checkout_token' => (string) Str::uuid(),
        ];
    }
}
