<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $carts,
        private CheckoutService $checkout,
    ) {}

    public function create(Request $request): Response
    {
        $cart = $this->carts->forUser($request->user());
        $addresses = $request->user()->addresses()->with('deliveryZone')->latest('is_default')->orderBy('id')->get();
        $selected = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        return Inertia::render('Customer/Checkout', [
            'addresses' => $addresses,
            'summary' => $this->carts->summary($cart, $selected),
            'checkoutToken' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $data = $request->validate([
            'address_id' => ['required', 'exists:customer_addresses,id'],
            'checkout_token' => ['required', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $address = CustomerAddress::query()->findOrFail($data['address_id']);
        $this->authorize('view', $address);

        $order = $this->checkout->place(
            $request->user(),
            $address,
            $data['checkout_token'],
            $data['notes'] ?? null,
        );

        return redirect()->route('customer.orders.show', $order)->with('success', __('تم إنشاء الطلب بنجاح.'));
    }
}
