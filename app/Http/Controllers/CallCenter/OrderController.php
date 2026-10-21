<?php

namespace App\Http\Controllers\CallCenter;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load(['customer.addresses', 'business', 'items', 'statusHistories.actor', 'driver', 'payment']);

        return Inertia::render('CallCenter/OrderShow', [
            'order' => $order,
            'mapNote' => 'لا يوجد تتبع حي للموقع. يظهر العنوان المحفوظ فقط.',
        ]);
    }

    public function confirm(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('confirm', $order);
        $workflow->transition($order, OrderStatus::Confirmed, $request->user(), $request->string('reason')->toString() ?: null);

        return back()->with('success', __('تم تأكيد الطلب.'));
    }

    public function reject(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $workflow->transition($order, OrderStatus::Cancelled, $request->user(), $data['reason']);

        return back()->with('success', __('تم رفض الطلب.'));
    }

    public function assign(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('assignDriver', $order);

        $data = $request->validate([
            'driver_id' => ['required', 'exists:users,id'],
        ]);

        $workflow->assignDriver($order, User::query()->findOrFail($data['driver_id']), $request->user());

        return back()->with('success', __('تم تعيين المندوب.'));
    }

    public function note(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('note', $order);

        $data = $request->validate([
            'internal_notes' => ['required', 'string', 'max:1000'],
        ]);

        $order->update(['internal_notes' => $data['internal_notes']]);

        return back()->with('success', __('تم حفظ الملاحظة الداخلية.'));
    }

    public function transition(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('update', $order);

        $data = $request->validate([
            'status' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $workflow->transition($order, OrderStatus::from($data['status']), $request->user(), $data['reason'] ?? null);

        return back()->with('success', __('تم تحديث حالة الطلب.'));
    }
}
