<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $orders = $request->user()
            ->orders()
            ->with('business')
            ->latest()
            ->orderByDesc('id')
            ->paginate(10);

        return Inertia::render('Customer/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load([
            'business',
            'items',
            'statusHistories.actor',
            'payment',
            'driver:id,name,phone',
        ]);

        return Inertia::render('Customer/Orders/Show', [
            'order' => $order,
        ]);
    }

    public function reorder(Request $request, Order $order, CartService $carts): RedirectResponse
    {
        $this->authorize('view', $order);
        $order->load('items.product');

        foreach ($order->items as $item) {
            if (! $item->product) {
                continue;
            }

            $carts->addItem($request->user(), $item->product, $item->quantity);
        }

        return redirect()->route('customer.cart')->with('success', __('تمت إعادة الطلب إلى السلة.'));
    }

    public function cancel(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $workflow->transition($order, OrderStatus::Cancelled, $request->user(), $data['reason'] ?? 'إلغاء من العميل');

        return back()->with('success', __('تم إلغاء الطلب.'));
    }
}
