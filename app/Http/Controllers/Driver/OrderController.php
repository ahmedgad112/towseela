<?php

namespace App\Http\Controllers\Driver;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load(['business', 'items', 'customer:id,name,phone']);

        return Inertia::render('Driver/OrderShow', [
            'order' => $order,
            'mapNote' => 'لا يوجد تتبع GPS مباشر. استخدم العنوان المحفوظ أو رابط الخرائط الخارجي.',
        ]);
    }

    public function pickup(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('pickup', $order);
        $workflow->transition($order, OrderStatus::PickedUp, $request->user());

        return back()->with('success', __('تم تأكيد استلام الطلب.'));
    }

    public function outForDelivery(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('pickup', $order);
        $workflow->transition($order, OrderStatus::OutForDelivery, $request->user());

        return back()->with('success', __('الطلب في الطريق الآن.'));
    }

    public function deliver(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('markDelivered', $order);

        $data = $request->validate([
            'proof_of_delivery' => ['nullable', 'string', 'max:255'],
        ]);

        $workflow->markDelivered($order, $request->user(), $data['proof_of_delivery'] ?? null);

        return redirect()->route('driver.dashboard')->with('success', __('تم تأكيد التسليم.'));
    }

    public function fail(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('markDelivered', $order);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $workflow->transition($order, OrderStatus::DeliveryFailed, $request->user(), $data['reason']);

        return redirect()->route('driver.dashboard')->with('success', __('تم تسجيل فشل التسليم.'));
    }

    public function history(Request $request): Response
    {
        abort_unless($request->user()->hasRole('driver'), 403);

        return Inertia::render('Driver/History', [
            'orders' => Order::query()
                ->where('driver_id', $request->user()->id)
                ->with('business')
                ->latest()
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }
}
