<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): Response
    {
        abort_unless($request->user()->can('dashboard.view'), 403);

        $from = $request->date('from');
        $to = $request->date('to');

        return Inertia::render('Admin/Dashboard', [
            'filters' => ['from' => $from?->toDateString(), 'to' => $to?->toDateString()],
            'metrics' => $reports->dashboard($from, $to),
            'salesByBusiness' => $reports->salesByBusiness($from, $to),
        ]);
    }
}
