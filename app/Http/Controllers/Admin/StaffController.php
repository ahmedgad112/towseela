<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('users.view'), 403);

        return Inertia::render('Admin/Staff/Index', [
            'staff' => User::query()
                ->whereHas('roles', fn ($query) => $query->whereNotIn('name', ['customer', 'driver']))
                ->with('roles')
                ->orderBy('name')
                ->paginate(15),
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->whereNotIn('name', ['super_admin', 'customer', 'driver'])
                ->orderBy('name')
                ->get(['id', 'name', 'label']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('users.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
                Rule::notIn(['super_admin', 'customer', 'driver']),
            ],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        $user->assignRole($data['role']);

        return back()->with('success', __('تم إنشاء حساب الموظف.'));
    }
}
