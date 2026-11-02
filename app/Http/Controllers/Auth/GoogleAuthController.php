<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $this->ensureGoogleConfigured();

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request, PlatformSettings $settings): RedirectResponse
    {
        $this->ensureGoogleConfigured();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()
                ->route('login')
                ->with('error', __('انتهت صلاحية جلسة Google. حاول مرة أخرى.'));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->with('error', __('تعذر تسجيل الدخول عبر Google. حاول مرة أخرى.'));
        }

        $email = $googleUser->getEmail();

        if (! filled($email)) {
            return redirect()
                ->route('login')
                ->with('error', __('حساب Google لا يحتوي على بريد إلكتروني صالح.'));
        }

        $googleId = (string) $googleUser->getId();

        $user = User::query()->where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();

            if ($user) {
                $user->forceFill([
                    'google_id' => $googleId,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            } else {
                if (! $settings->features()['customer_registration_enabled']) {
                    return redirect()
                        ->route('login')
                        ->with('error', __('إنشاء الحسابات معطل حالياً.'));
                }

                $user = User::query()->create([
                    'name' => $googleUser->getName() ?: strstr($email, '@', true) ?: $email,
                    'email' => $email,
                    'google_id' => $googleId,
                    'email_verified_at' => now(),
                    'password' => null,
                    'is_active' => true,
                ]);

                $user->assignRole('customer');
            }
        }

        if (! $user->is_active) {
            return redirect()
                ->route('login')
                ->with('error', __('الحساب غير نشط.'));
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended($user->portalHome());
    }

    private function ensureGoogleConfigured(): void
    {
        if (! filled(config('services.google.client_id')) || ! filled(config('services.google.client_secret'))) {
            throw new HttpException(503, __('تسجيل الدخول عبر Google غير مفعّل.'));
        }
    }
}
