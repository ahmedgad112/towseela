<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(PlatformSettings $settings): Response
    {
        abort_unless($settings->features()['customer_registration_enabled'], 403);

        return Inertia::render('Auth/Register');
    }

    public function store(Request $request, PlatformSettings $settings): RedirectResponse
    {
        abort_unless($settings->features()['customer_registration_enabled'], 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->create([
            ...$data,
            'is_active' => true,
        ]);

        $user->assignRole('customer');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('customer.home');
    }
}
