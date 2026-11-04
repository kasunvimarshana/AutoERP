<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\VehicleRental\Http\Requests\RunningChartReportRequest;
use Modules\VehicleRental\Services\RunningChartReportService;
use Spatie\LaravelPdf\Facades\Pdf;

final class RunningChartReportController
{
    public function __construct(
        private readonly RunningChartReportService $reports,
    ) {}

    public function show(RunningChartReportRequest $request): JsonResponse
    {
        $data = $this->reports->generate(
            $request->context(),
            $request->only(['agreement_id', 'start_date', 'end_date', 'vehicle_use_id', 'sheet_mode']),
        );

        return response()->json($data);
    }

    public function export(RunningChartReportRequest $request, string $format): Response
    {
        $data = $this->reports->generate(
            $request->context(),
            $request->only(['agreement_id', 'start_date', 'end_date', 'vehicle_use_id', 'sheet_mode']),
        );

        if ($format === 'pdf') {
            $filename = "running-chart-{$data['header']['agreement_number']}-{$data['header']['start_date']}-to-{$data['header']['end_date']}.pdf";

            return Pdf::view('reports.vehicle-rental.running-chart-sheet', array_merge($data, ['mode' => 'pdf']))
                ->format('A4')
                ->orientation('landscape')
                ->margins(8, 10, 8, 10)
                ->name($filename)
                ->download()
                ->toResponse($request);
        }

        if ($format === 'print') {
            return response()->view(
                'reports.vehicle-rental.running-chart-sheet',
                array_merge($data, ['mode' => 'print']),
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        }

        if ($format === 'html') {
            return response()->view(
                'reports.vehicle-rental.running-chart-sheet',
                array_merge($data, ['mode' => 'preview']),
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        }

        abort(404, 'Unsupported format.');
    }
}
