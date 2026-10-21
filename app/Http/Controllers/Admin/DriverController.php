<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('drivers.view'), 403);

        $drivers = User::role('driver')
            ->with('driverProfile')
            ->withCount([
                'assignedOrders as active_assignments_count' => fn ($query) => $query->whereIn('status', array_map(
                    fn (OrderStatus $status): string => $status->value,
                    array_filter(OrderStatus::cases(), fn (OrderStatus $status): bool => $status->isActiveAssignment()),
                )),
            ])
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Admin/Drivers/Index', [
            'drivers' => $drivers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('drivers.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        $user->assignRole('driver');
        $user->driverProfile()->create([
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'is_available' => $request->boolean('is_available', true),
            'status' => $request->boolean('is_available', true) ? 'available' : 'offline',
        ]);

        return back()->with('success', __('تم إنشاء حساب المندوب.'));
    }

    public function update(Request $request, User $driver): RedirectResponse
    {
        abort_unless($request->user()->can('drivers.manage'), 403);
        abort_unless($driver->hasRole('driver'), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($driver->id)],
            'phone' => ['required', 'string', Rule::unique('users', 'phone')->ignore($driver->id)],
            'is_active' => ['sometimes', 'boolean'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        $driver->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'is_active' => $request->boolean('is_active', $driver->is_active),
        ]);

        $driver->driverProfile()->updateOrCreate(
            ['user_id' => $driver->id],
            [
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'is_available' => $request->boolean('is_available'),
                'status' => $request->boolean('is_available') ? 'available' : 'offline',
            ],
        );

        return back()->with('success', __('تم تحديث المندوب.'));
    }
}
