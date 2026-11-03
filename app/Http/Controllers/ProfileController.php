<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render($this->profilePage($user), [
            'user' => [
                ...$user->only(['id', 'name', 'email', 'phone']),
                'google_linked' => filled($user->google_id),
                'has_password' => filled($user->password),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        $user->update($data);

        return back()->with('success', __('تم تحديث الحساب.'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $requiresCurrentPassword = filled($user->password);

        $data = $request->validate([
            'current_password' => $requiresCurrentPassword
                ? ['required', 'current_password']
                : ['nullable'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => $data['password'],
        ]);

        Auth::logoutOtherDevices($data['password']);

        return back()->with('success', __('تم تغيير كلمة المرور.'));
    }

    private function profilePage(User $user): string
    {
        return match (true) {
            $user->hasAnyRole(['super_admin', 'admin', 'business_manager']) => 'Admin/Profile',
            $user->hasAnyRole(['call_center_agent', 'dispatcher']) => 'CallCenter/Profile',
            $user->hasRole('driver') => 'Driver/Profile',
            default => 'Customer/Profile',
        };
    }
}
