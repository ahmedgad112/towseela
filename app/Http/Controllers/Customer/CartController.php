<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private CartService $carts) {}

    public function show(Request $request): Response
    {
        $cart = $this->carts->forUser($request->user());
        $request->user()->loadMissing('defaultAddress.deliveryZone.feeRules');

        return Inertia::render('Customer/Cart', [
            'summary' => $this->carts->summary($cart, $request->user()->defaultAddress),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'options' => ['array'],
            'options.*' => ['integer', 'exists:product_option_values,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $this->carts->addItem(
            $request->user(),
            Product::query()->findOrFail($data['product_id']),
            $data['quantity'],
            $data['options'] ?? [],
            $data['notes'] ?? null,
        );

        if ($request->expectsJson()) {
            return response()->json($this->carts->snapshot($this->carts->forUser($request->user())));
        }

        return back()->with('success', __('تمت إضافة المنتج إلى السلة.'));
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse|RedirectResponse
    {
        abort_unless($cartItem->cart?->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $this->carts->updateQuantity($cartItem, $data['quantity']);

        if ($request->expectsJson()) {
            return response()->json($this->carts->snapshot($this->carts->forUser($request->user())));
        }

        return back();
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse|RedirectResponse
    {
        abort_unless($cartItem->cart?->user_id === $request->user()->id, 403);
        $this->carts->removeItem($cartItem);

        if ($request->expectsJson()) {
            return response()->json($this->carts->snapshot($this->carts->forUser($request->user())));
        }

        return back();
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $this->carts->applyCoupon($this->carts->forUser($request->user()), $data['code']);

        return back()->with('success', __('تم تطبيق كود الخصم.'));
    }
}
