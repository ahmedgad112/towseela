<?php

namespace App\Http\Middleware;

use App\Services\CartService;
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

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'locale' => app()->getLocale(),
            'currencyLabel' => config('towseela.currency_label'),
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

                $user->loadMissing('defaultAddress.deliveryZone.feeRules');

                return app(CartService::class)->summary(
                    app(CartService::class)->forUser($user),
                    $user->defaultAddress,
                );
            },
            'customerLocation' => function () use ($user) {
                $mapCenter = [
                    'lat' => (float) config('towseela.map.default_lat'),
                    'lng' => (float) config('towseela.map.default_lng'),
                ];

                if (! $user || ! $user->hasRole('customer')) {
                    return [
                        'hasAddress' => false,
                        'defaultAddress' => null,
                        'mapCenter' => $mapCenter,
                    ];
                }

                $user->loadMissing('defaultAddress');

                return [
                    'hasAddress' => $user->addresses()->exists(),
                    'defaultAddress' => $user->defaultAddress?->only(['id', 'line1', 'city', 'lat', 'lng']),
                    'mapCenter' => $mapCenter,
                ];
            },
        ];

    }
}
