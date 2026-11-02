<?php

namespace App\Http\Controllers\Realtime;

use App\Http\Controllers\Controller;
use App\Support\OrderRealtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderPulseController extends Controller
{
    public function __invoke(Request $request, OrderRealtime $realtime): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $payload = [
            'ops' => null,
            'driver' => null,
            'customer' => null,
        ];

        if ($user->hasAnyRole([
            'super_admin',
            'admin',
            'business_manager',
            'call_center_agent',
            'dispatcher',
        ])) {
            $payload['ops'] = $realtime->ops();
        }

        if ($user->hasRole('driver')) {
            $payload['driver'] = $realtime->forDriver($user->id);
        }

        if ($user->hasRole('customer')) {
            $payload['customer'] = $realtime->forCustomer($user->id);
        }

        abort_if(
            $payload['ops'] === null
            && $payload['driver'] === null
            && $payload['customer'] === null,
            403,
        );

        return response()->json($payload);
    }
}
