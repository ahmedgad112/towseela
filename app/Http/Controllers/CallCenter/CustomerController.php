<?php

namespace App\Http\Controllers\CallCenter;

use App\Enums\AddressLabel;
use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerController extends Controller
{
    public function search(Request $request, CustomerAccountService $customers): JsonResponse
    {
        abort_unless($request->user()->can('customers.view'), 403);

        $term = $request->string('q')->toString();

        return response()->json([
            'customers' => $customers->search($term)->map(fn (User $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'orders_count' => $customer->orders_count,
                'addresses' => $customer->addresses->map(fn (CustomerAddress $address) => [
                    'id' => $address->id,
                    'label' => $address->label?->value,
                    'label_text' => $address->label?->label(),
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'delivery_zone_id' => $address->delivery_zone_id,
                    'delivery_zone' => $address->deliveryZone?->only(['id', 'name_ar', 'governorate']),
                    'is_default' => $address->is_default,
                ]),
            ]),
        ]);
    }

    public function store(Request $request, CustomerAccountService $customers): JsonResponse
    {
        abort_unless($request->user()->can('customers.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', Password::defaults()],
        ]);

        $customer = $customers->create($data, $request->user());

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'orders_count' => 0,
                'addresses' => [],
            ],
        ], 201);
    }

    public function storeAddress(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->can('customers.create'), 403);
        abort_unless($user->hasRole('customer'), 404);

        $data = $request->validate([
            'label' => ['required', Rule::enum(AddressLabel::class)],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'delivery_zone_id' => ['required', 'exists:delivery_zones,id'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $address = DB::transaction(function () use ($user, $data, $request) {
            $address = $user->addresses()->create($data);

            if ($request->boolean('is_default') || ! $user->addresses()->where('is_default', true)->whereKeyNot($address->id)->exists()) {
                $user->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
                $address->update(['is_default' => true]);
            }

            return $address->load('deliveryZone');
        });

        return response()->json([
            'address' => [
                'id' => $address->id,
                'label' => $address->label?->value,
                'label_text' => $address->label?->label(),
                'line1' => $address->line1,
                'line2' => $address->line2,
                'city' => $address->city,
                'delivery_zone_id' => $address->delivery_zone_id,
                'delivery_zone' => $address->deliveryZone?->only(['id', 'name_ar', 'governorate']),
                'is_default' => $address->is_default,
            ],
        ], 201);
    }
}
