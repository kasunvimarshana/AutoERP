<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\RentalRunningChartReportRequest;
use Modules\VehicleRental\Services\RentalRunningChartReportService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;
use Spatie\LaravelPdf\Facades\Pdf;

final class RentalRunningChartReportController
{
    use ScopesVehicleRentalRequests;

    public function __construct(
        private readonly VehicleRentalAuthorizationService $authorization,
        private readonly RentalRunningChartReportService $reports,
    ) {}

    public function show(RentalRunningChartReportRequest $request): JsonResponse
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::VIEW,
        );

        $data = $this->reports->generate(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated(),
        );

        return response()->json($data);
    }

    public function export(RentalRunningChartReportRequest $request, string $format): Response
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::VIEW,
        );

        $data = $this->reports->generate(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated(),
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
