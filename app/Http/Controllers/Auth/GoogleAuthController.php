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
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $this->ensureGoogleConfigured();

        $request->session()->put(
            'google_auth_intent',
            $request->user() ? 'link' : 'login',
        );

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request, PlatformSettings $settings): RedirectResponse
    {
        $this->ensureGoogleConfigured();

        $intent = $request->session()->pull('google_auth_intent', 'login');

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return $this->googleFailureRedirect(
                $request,
                $intent,
                __('انتهت صلاحية جلسة Google. حاول مرة أخرى.'),
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->googleFailureRedirect(
                $request,
                $intent,
                __('تعذر تسجيل الدخول عبر Google. حاول مرة أخرى.'),
            );
        }

        $email = $googleUser->getEmail();

        if (! filled($email)) {
            return $this->googleFailureRedirect(
                $request,
                $intent,
                __('حساب Google لا يحتوي على بريد إلكتروني صالح.'),
            );
        }

        if ($intent === 'link' && $request->user()) {
            return $this->linkToAuthenticatedUser($request, $googleUser);
        }

        return $this->loginOrRegister($request, $settings, $googleUser, $email);
    }

    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! filled($user->google_id)) {
            return back()->with('error', __('حساب Google غير مرتبط.'));
        }

        if (! filled($user->password)) {
            return back()->with('error', __('أضف كلمة مرور أولاً قبل فصل حساب Google.'));
        }

        $user->forceFill(['google_id' => null])->save();

        return back()->with('success', __('تم فصل حساب Google.'));
    }

    private function loginOrRegister(
        Request $request,
        PlatformSettings $settings,
        SocialiteUser $googleUser,
        string $email,
    ): RedirectResponse {
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

        $user->clearExpiredBlock();

        if (! $user->is_active) {
            return redirect()
                ->route('login')
                ->with('error', __('الحساب غير نشط.'));
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended($user->portalHome());
    }

    private function linkToAuthenticatedUser(Request $request, SocialiteUser $googleUser): RedirectResponse
    {
        $user = $request->user();
        $email = (string) $googleUser->getEmail();
        $googleId = (string) $googleUser->getId();

        if (strcasecmp((string) $user->email, $email) !== 0) {
            return redirect()
                ->to($user->profilePath())
                ->with('error', __('يجب أن يطابق بريد Google بريد حسابك الحالي (:email).', [
                    'email' => $user->email,
                ]));
        }

        $takenByAnotherUser = User::query()
            ->where('google_id', $googleId)
            ->whereKeyNot($user->id)
            ->exists();

        if ($takenByAnotherUser) {
            return redirect()
                ->to($user->profilePath())
                ->with('error', __('حساب Google هذا مرتبط بحساب آخر.'));
        }

        $user->forceFill([
            'google_id' => $googleId,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return redirect()
            ->to($user->profilePath())
            ->with('success', __('تم ربط حساب Google بنجاح.'));
    }

    private function googleFailureRedirect(Request $request, string $intent, string $message): RedirectResponse
    {
        if ($intent === 'link' && $request->user()) {
            return redirect()
                ->to($request->user()->profilePath())
                ->with('error', $message);
        }

        return redirect()
            ->route('login')
            ->with('error', $message);
    }

    private function ensureGoogleConfigured(): void
    {
        if (! filled(config('services.google.client_id')) || ! filled(config('services.google.client_secret'))) {
            throw new HttpException(503, __('تسجيل الدخول عبر Google غير مفعّل.'));
        }
    }
}
