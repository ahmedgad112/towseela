<?php

namespace App\Http\Controllers\CallCenter;

use App\Enums\AddressLabel;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\CustomerAddress;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderWorkflowService;
use App\Support\OrderTransitions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function create(Request $request): Response
    {
        $this->authorize('create', Order::class);

        $businesses = Business::query()
            ->with('category:id,name_ar,icon')
            ->where('is_active', true)
            ->where('accepts_orders', true)
            ->orderBy('name_ar')
            ->get([
                'id',
                'business_category_id',
                'name_ar',
                'description',
                'cover_path',
                'min_order_amount',
                'delivery_time_min',
                'delivery_time_max',
            ]);

        return Inertia::render('CallCenter/Orders/Create', [
            'categories' => BusinessCategory::query()
                ->active()
                ->withCount([
                    'businesses as accepting_orders_count' => fn ($query) => $query
                        ->where('is_active', true)
                        ->where('accepts_orders', true),
                ])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name_ar', 'icon'])
                ->filter(fn (BusinessCategory $category) => $category->accepting_orders_count > 0)
                ->values()
                ->map(fn (BusinessCategory $category) => [
                    'id' => $category->id,
                    'name_ar' => $category->name_ar,
                    'icon' => $category->icon,
                    'businesses_count' => $category->accepting_orders_count,
                ]),
            'businesses' => $businesses->map(fn (Business $business) => [
                'id' => $business->id,
                'business_category_id' => $business->business_category_id,
                'name_ar' => $business->name_ar,
                'description' => $business->description,
                'cover_url' => $business->cover_url,
                'min_order_amount' => $business->min_order_amount,
                'delivery_time_min' => $business->delivery_time_min,
                'delivery_time_max' => $business->delivery_time_max,
                'category' => $business->category
                    ? $business->category->only(['id', 'name_ar', 'icon'])
                    : null,
            ]),
            'zones' => DeliveryZone::query()
                ->active()
                ->orderBy('governorate')
                ->orderBy('name_ar')
                ->get(['id', 'name_ar', 'governorate']),
            'governorates' => collect(config('towseela.governorates'))->pluck('name')->values(),
            'labels' => collect(AddressLabel::cases())->map(fn (AddressLabel $label) => [
                'value' => $label->value,
                'label' => $label->label(),
            ]),
            'checkoutToken' => (string) Str::uuid(),
            'can' => [
                'createCustomer' => $request->user()->can('customers.create'),
            ],
        ]);
    }

    public function products(Request $request, Business $business): JsonResponse
    {
        $this->authorize('create', Order::class);
        abort_unless($business->is_active && $business->accepts_orders, 404);

        $products = $business->products()
            ->available()
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get(['id', 'business_id', 'name_ar', 'price', 'is_available', 'is_active']);

        return response()->json([
            'products' => $products,
        ]);
    }

    public function store(Request $request, CheckoutService $checkout): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:users,id'],
            'address_id' => ['required', 'exists:customer_addresses,id'],
            'checkout_token' => ['required', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.options' => ['sometimes', 'array'],
            'items.*.options.*' => ['integer', 'exists:product_option_values,id'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $customer = User::query()->findOrFail($data['customer_id']);
        abort_unless($customer->hasRole('customer'), 422);

        $address = CustomerAddress::query()->findOrFail($data['address_id']);
        abort_unless($address->user_id === $customer->id, 422);

        $order = $checkout->placeAssisted(
            $customer,
            $address,
            $data['items'],
            $request->user(),
            $data['checkout_token'],
            $data['notes'] ?? null,
        );

        return redirect()
            ->route('call-center.orders.show', $order)
            ->with('success', __('تم إنشاء الطلب من مركز الاتصال.'));
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load(['customer.addresses', 'business', 'items', 'statusHistories.actor', 'driver', 'payment']);
        $transitions = OrderTransitions::availableFor($request->user(), $order);

        return Inertia::render('CallCenter/OrderShow', [
            'order' => $order,
            'mapNote' => 'لا يوجد تتبع حي للموقع. يظهر العنوان المحفوظ فقط.',
            'transitions' => $transitions,
            'can' => [
                'confirm' => $request->user()->can('confirm', $order)
                    && $order->status->canTransitionTo(OrderStatus::Confirmed),
                'cancel' => $request->user()->can('cancel', $order)
                    && $order->status->canTransitionTo(OrderStatus::Cancelled),
                'note' => $request->user()->can('note', $order),
                'updateStatus' => $transitions !== [],
            ],
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
}
