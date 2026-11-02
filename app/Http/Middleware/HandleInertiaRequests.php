<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use App\Support\PlatformSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Spatie\Permission\Models\Permission;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $platform = app(PlatformSettings::class);
        $brand = $platform->brand();
        $operations = $platform->operations();
        $map = $platform->map();
        $features = $platform->features();

        return [
            ...parent::share($request),
            'appName' => $brand['name_ar'] ?: config('app.name'),
            'locale' => app()->getLocale(),
            'currencyLabel' => $operations['currency_label'],
            'platform' => [
                'brand' => $brand,
                'features' => $features,
                'google_auth_enabled' => filled(config('services.google.client_id'))
                    && filled(config('services.google.client_secret')),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'auth' => [
                'user' => $user?->only(['id', 'name', 'email', 'phone']),
                'roles' => $user?->getRoleNames() ?? [],
                'permissions' => $user?->hasRole('super_admin')
                    ? Permission::query()->pluck('name')
                    : ($user?->getAllPermissions()->pluck('name') ?? []),
            ],
            'cart' => function () use ($user) {
                if (! $user || ! $user->hasRole('customer')) {
                    return null;
                }

                return app(CartService::class)->snapshot(
                    app(CartService::class)->forUser($user),
                );
            },
            'customerLocation' => function () use ($user, $map) {
                $mapCenter = [
                    'lat' => $map['default_lat'],
                    'lng' => $map['default_lng'],
                ];

                if (! $user || ! $user->hasRole('customer')) {
                    return [
                        'hasAddress' => false,
                        'defaultAddress' => null,
                        'mapCenter' => $mapCenter,
                    ];
                }

                $user->loadMissing('defaultAddress');
                $defaultAddress = $user->defaultAddress;

                return [
                    'hasAddress' => $defaultAddress !== null ? true : $user->addresses()->exists(),
                    'defaultAddress' => $defaultAddress?->only(['id', 'line1', 'city', 'lat', 'lng']),
                    'mapCenter' => $mapCenter,
                ];
            },
        ];
    }
}
