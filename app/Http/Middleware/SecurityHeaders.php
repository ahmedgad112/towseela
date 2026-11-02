<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $security = config('towseela.security', []);

        if (! ($security['headers_enabled'] ?? true)) {
            return $response;
        }

        if ($security['nosniff'] ?? true) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        $frameGuard = (string) ($security['frame_guard'] ?? 'SAMEORIGIN');
        if ($frameGuard !== 'OFF') {
            $response->headers->set('X-Frame-Options', $frameGuard);
        }

        $response->headers->set('Referrer-Policy', (string) ($security['referrer_policy'] ?? 'strict-origin-when-cross-origin'));
        $response->headers->set('Permissions-Policy', (string) ($security['permissions_policy'] ?? 'camera=(), microphone=(), payment=(), usb=()'));
        $response->headers->set('Cross-Origin-Opener-Policy', (string) ($security['cross_origin_opener_policy'] ?? 'same-origin'));
        $response->headers->set('Cross-Origin-Resource-Policy', (string) ($security['cross_origin_resource_policy'] ?? 'same-origin'));
        $response->headers->remove('X-Powered-By');

        if (($security['hsts_enabled'] ?? true) && $request->secure()) {
            $maxAge = (int) ($security['hsts_max_age'] ?? 31536000);
            $response->headers->set('Strict-Transport-Security', 'max-age='.$maxAge.'; includeSubDomains');
        }

        return $response;
    }
}
