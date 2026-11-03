<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $user->refresh();
        $user->clearExpiredBlock();

        if ($user->is_active) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            abort(403, __('تم إيقاف حسابك.'));
        }

        return redirect()
            ->route('login')
            ->with('error', __('تم إيقاف حسابك. تواصل مع الدعم إن كنت تعتقد أن هذا خطأ.'));
    }
}
