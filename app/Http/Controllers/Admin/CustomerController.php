<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $user->load(['addresses.deliveryZone', 'orders.business']);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $user,
        ]);
    }
}
