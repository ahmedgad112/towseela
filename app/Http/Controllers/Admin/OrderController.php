<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflowService;
use App\Support\OrderTransitions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->with(['customer', 'business', 'driver'])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('q')->toString(), function ($query, $term) {
                $query->where(function ($builder) use ($term) {
                    $builder->where('number', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['q', 'status']),
            'statuses' => collect(OrderStatus::cases())->map(fn (OrderStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load(['customer', 'business', 'driver', 'items', 'statusHistories.actor', 'assignments.driver', 'payment']);
        $transitions = OrderTransitions::availableFor($request->user(), $order);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
            'drivers' => User::role('driver')->active()->with('driverProfile')->orderBy('name')->get(['id', 'name', 'phone']),
            'transitions' => $transitions,
            'can' => [
                'assignDriver' => $request->user()->can('assignDriver', $order),
                'updateStatus' => $transitions !== [],
                'cancel' => $request->user()->can('cancel', $order)
                    && $order->status->canTransitionTo(OrderStatus::Cancelled),
                'delete' => $request->user()->can('delete', $order),
            ],
        ]);
    }

    public function transition(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $status = OrderStatus::from($data['status']);

        abort_unless(OrderTransitions::actorMayTransitionTo($request->user(), $order, $status), 403);

        if ($status === OrderStatus::Cancelled) {
            $request->validate([
                'reason' => ['required', 'string', 'max:255'],
            ]);
        }

        $workflow->transition($order, $status, $request->user(), $data['reason'] ?? null);

        return back()->with('success', __('تم تحديث حالة الطلب.'));
    }

    public function cancel(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $workflow->transition($order, OrderStatus::Cancelled, $request->user(), $data['reason']);

        return back()->with('success', __('تم إلغاء الطلب.'));
    }

    public function destroy(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('delete', $order);

        $workflow->delete($order, $request->user());

        return redirect()
            ->route('admin.orders.index')
            ->with('success', __('تم حذف الطلب.'));
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
}
