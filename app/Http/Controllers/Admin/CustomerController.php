<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('users.view'), 403);

        $customers = User::role('customer')
            ->withCount('orders')
            ->when($request->string('q')->toString(), function ($query, $term) {
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
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        abort_unless($request->user()->can('users.view'), 403);
        abort_unless($user->hasRole('customer'), 404);

        $user->load(['addresses.deliveryZone', 'orders.business']);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $user,
        ]);
    }
}
