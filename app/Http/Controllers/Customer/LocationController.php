<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(private GeocodingService $geocoding) {}

    public function reverse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $place = $this->geocoding->reverse((float) $data['lat'], (float) $data['lng']);

        if ($place === null) {
            return response()->json([
                'message' => __('تعذر تحديد العنوان من هذا الموقع.'),
            ], 422);
        }

        return response()->json($place);
    }

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        return response()->json([
            'results' => $this->geocoding->search($data['q']),
        ]);
    }
}
