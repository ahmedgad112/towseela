<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CustomerAccountService;
use App\Services\CustomerModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('customers.view'), 403);

        $term = $request->string('q')->toString();

        $customers = User::role('customer')
            ->withCount('orders')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($builder) use ($term) {
                    $builder->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->through(fn (User $customer) => $this->customerListPayload($customer))
            ->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only('q'),
            'can' => [
                'create' => $request->user()->can('customers.create'),
            ],
        ]);
    }

    public function store(Request $request, CustomerAccountService $customers): RedirectResponse
    {
        abort_unless($request->user()->can('customers.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', Password::defaults()],
        ]);

        $customer = $customers->create($data, $request->user());

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', __('تم إنشاء العميل.'));
    }

    public function show(Request $request, User $user): Response
    {
        abort_unless($request->user()->can('customers.view'), 403);
        abort_unless($user->hasRole('customer'), 404);

        $user->clearExpiredBlock();
        $user->load(['addresses.deliveryZone', 'orders.business']);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $this->customerShowPayload($user),
            'can' => [
                'update' => $request->user()->can('customers.update'),
                'delete' => $request->user()->can('customers.delete'),
            ],
        ]);
    }

    public function block(Request $request, User $user, CustomerModerationService $moderation): RedirectResponse
    {
        abort_unless($request->user()->can('customers.update'), 403);
        abort_unless($user->hasRole('customer'), 404);

        $data = $request->validate([
            'duration' => ['required', 'string', Rule::in(['permanent', '1_day', '7_days', '30_days', 'custom'])],
            'blocked_until' => ['nullable', 'required_if:duration,custom', 'date', 'after:now'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $moderation->block($user, $data, $request->user());

        return back()->with('success', __('تم حظر العميل.'));
    }

    public function unblock(Request $request, User $user, CustomerModerationService $moderation): RedirectResponse
    {
        abort_unless($request->user()->can('customers.update'), 403);
        abort_unless($user->hasRole('customer'), 404);

        $moderation->unblock($user, $request->user());

        return back()->with('success', __('تم فك حظر العميل.'));
    }

    public function destroy(Request $request, User $user, CustomerModerationService $moderation): RedirectResponse
    {
        abort_unless($request->user()->can('customers.delete'), 403);
        abort_unless($user->hasRole('customer'), 404);

        $moderation->delete($user, $request->user());

        return redirect()
            ->route('admin.customers.index')
            ->with('success', __('تم حذف العميل.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function customerListPayload(User $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'is_active' => $customer->is_active,
            'blocked_until' => $customer->blocked_until?->toIso8601String(),
            'orders_count' => $customer->orders_count,
            'block_status' => $this->blockStatus($customer),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customerShowPayload(User $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'is_active' => $customer->is_active,
            'blocked_until' => $customer->blocked_until?->toIso8601String(),
            'block_reason' => $customer->block_reason,
            'block_status' => $this->blockStatus($customer),
            'created_at' => $customer->created_at?->toIso8601String(),
            'addresses' => $customer->addresses,
            'orders' => $customer->orders,
            'orders_count' => $customer->orders->count(),
        ];
    }

    private function blockStatus(User $customer): string
    {
        if ($customer->is_active) {
            return 'active';
        }

        if ($customer->blocked_until !== null) {
            return 'temporary';
        }

        return 'permanent';
    }
}
