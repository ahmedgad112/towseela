<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): Response
    {
        abort_unless($request->user()->hasRole('driver'), 403);

        $queue = Order::query()
            ->where('driver_id', $request->user()->id)
            ->whereIn('status', ['assigned_to_driver', 'picked_up', 'out_for_delivery'])
            ->with('business')
            ->latest('assigned_at')
            ->orderByDesc('id')
            ->get();

        $performance = $reports->driverPerformance()->firstWhere('id', $request->user()->id);

        return Inertia::render('Driver/Dashboard', [
            'queue' => $queue,
            'performance' => $performance,
            'available' => (bool) $request->user()->driverProfile?->is_available,
        ]);
    }
}
