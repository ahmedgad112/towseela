<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): Response
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $from = $request->date('from');
        $to = $request->date('to');

        return Inertia::render('Admin/Reports/Index', [
            'filters' => ['from' => $from?->toDateString(), 'to' => $to?->toDateString()],
            'metrics' => $reports->dashboard($from, $to),
            'salesByBusiness' => $reports->salesByBusiness($from, $to),
            'driverPerformance' => $reports->driverPerformance($from, $to),
        ]);
    }

    public function export(Request $request, ReportService $reports): StreamedResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        $rows = $reports->salesByBusiness($request->date('from'), $request->date('to'));

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['business', 'orders', 'sales']);

            foreach ($rows as $row) {
                fputcsv($handle, [$row['name'], $row['orders'], $row['sales']]);
            }

            fclose($handle);
        }, 'towseela-sales.csv');
    }
}
