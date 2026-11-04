<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\VehicleRental\Http\Requests\FleetUtilizationReportRequest;
use Modules\VehicleRental\Http\Requests\LesseeOutstandingReportRequest;
use Modules\VehicleRental\Http\Requests\OwnerDisbursementReportRequest;
use Modules\VehicleRental\Http\Requests\RentalIncidentReportRequest;
use Modules\VehicleRental\Http\Requests\RentalRevenueReportRequest;
use Modules\VehicleRental\Http\Requests\VehicleProfitabilityReportRequest;
use Modules\VehicleRental\Http\Requests\VehicleRevenueLicenseReportRequest;
use Modules\VehicleRental\Services\FleetUtilizationReportService;
use Modules\VehicleRental\Services\LesseeOutstandingReportService;
use Modules\VehicleRental\Services\OwnerDisbursementReportService;
use Modules\VehicleRental\Services\RentalIncidentReportService;
use Modules\VehicleRental\Services\RentalRevenueReportService;
use Modules\VehicleRental\Services\VehicleProfitabilityReportService;
use Modules\VehicleRental\Services\VehicleRevenueLicenseReportService;
use Spatie\LaravelPdf\Facades\Pdf;

final class RentalReportsController
{
    public function __construct(
        private readonly FleetUtilizationReportService $fleetUtilizationService,
        private readonly VehicleRevenueLicenseReportService $revenueLicenseService,
        private readonly RentalRevenueReportService $rentalRevenueService,
        private readonly LesseeOutstandingReportService $lesseeOutstandingService,
        private readonly OwnerDisbursementReportService $ownerDisbursementService,
        private readonly VehicleProfitabilityReportService $vehicleProfitabilityService,
        private readonly RentalIncidentReportService $incidentReportService,
    ) {}

    public function fleetUtilization(FleetUtilizationReportRequest $request): JsonResponse
    {
        $data = $this->fleetUtilizationService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'ownership_type', 'custody_status', 'vehicle_id']),
        );

        return response()->json($data);
    }

    public function exportFleetUtilization(FleetUtilizationReportRequest $request, string $format): Response
    {
        $data = $this->fleetUtilizationService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'ownership_type', 'custody_status', 'vehicle_id']),
        );

        return $this->respondExport('reports.vehicle-rental.fleet-utilization', 'fleet-utilization', $data, $format, $request, 'landscape');
    }

    public function revenueLicenses(VehicleRevenueLicenseReportRequest $request): JsonResponse
    {
        $data = $this->revenueLicenseService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'status', 'vehicle_id']),
        );

        return response()->json($data);
    }

    public function exportRevenueLicenses(VehicleRevenueLicenseReportRequest $request, string $format): Response
    {
        $data = $this->revenueLicenseService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'status', 'vehicle_id']),
        );

        return $this->respondExport('reports.vehicle-rental.revenue-license', 'revenue-licenses', $data, $format, $request, 'portrait');
    }

    public function rentalRevenue(RentalRevenueReportRequest $request): JsonResponse
    {
        $data = $this->rentalRevenueService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'customer_id', 'agreement_id']),
        );

        return response()->json($data);
    }

    public function exportRentalRevenue(RentalRevenueReportRequest $request, string $format): Response
    {
        $data = $this->rentalRevenueService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'customer_id', 'agreement_id']),
        );

        return $this->respondExport('reports.vehicle-rental.rental-revenue', 'rental-revenue', $data, $format, $request, 'landscape');
    }

    public function lesseeOutstanding(LesseeOutstandingReportRequest $request): JsonResponse
    {
        $data = $this->lesseeOutstandingService->generate(
            $request->context(),
            $request->only(['as_of_date', 'customer_id', 'agreement_id', 'aging_bucket']),
        );

        return response()->json($data);
    }

    public function exportLesseeOutstanding(LesseeOutstandingReportRequest $request, string $format): Response
    {
        $data = $this->lesseeOutstandingService->generate(
            $request->context(),
            $request->only(['as_of_date', 'customer_id', 'agreement_id', 'aging_bucket']),
        );

        return $this->respondExport('reports.vehicle-rental.lessee-outstanding', 'lessee-outstanding', $data, $format, $request, 'portrait');
    }

    public function ownerDisbursements(OwnerDisbursementReportRequest $request): JsonResponse
    {
        $data = $this->ownerDisbursementService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'supplier_id', 'owner_agreement_id']),
        );

        return response()->json($data);
    }

    public function exportOwnerDisbursements(OwnerDisbursementReportRequest $request, string $format): Response
    {
        $data = $this->ownerDisbursementService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'supplier_id', 'owner_agreement_id']),
        );

        return $this->respondExport('reports.vehicle-rental.owner-disbursement', 'owner-disbursement', $data, $format, $request, 'landscape');
    }

    public function vehicleProfitability(VehicleProfitabilityReportRequest $request): JsonResponse
    {
        $data = $this->vehicleProfitabilityService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'ownership_type', 'vehicle_id']),
        );

        return response()->json($data);
    }

    public function exportVehicleProfitability(VehicleProfitabilityReportRequest $request, string $format): Response
    {
        $data = $this->vehicleProfitabilityService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'ownership_type', 'vehicle_id']),
        );

        return $this->respondExport('reports.vehicle-rental.vehicle-profitability', 'vehicle-profitability', $data, $format, $request, 'landscape');
    }

    public function incidentsSummary(RentalIncidentReportRequest $request): JsonResponse
    {
        $data = $this->incidentReportService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'incident_type', 'status', 'vehicle_id']),
        );

        return response()->json($data);
    }

    public function exportIncidentsSummary(RentalIncidentReportRequest $request, string $format): Response
    {
        $data = $this->incidentReportService->generate(
            $request->context(),
            $request->only(['start_date', 'end_date', 'incident_type', 'status', 'vehicle_id']),
        );

        return $this->respondExport('reports.vehicle-rental.rental-incidents', 'rental-incidents', $data, $format, $request, 'landscape');
    }

    private function respondExport(string $viewName, string $filenamePrefix, array $data, string $format, Request $request, string $orientation = 'landscape'): Response
    {
        if ($format === 'pdf') {
            $datePart = $data['header']['as_of_date'] ?? ($data['header']['start_date'] ?? 'export');
            $filename = "{$filenamePrefix}-{$datePart}.pdf";

            return Pdf::view($viewName, array_merge($data, ['mode' => 'pdf']))
                ->format('A4')
                ->orientation($orientation)
                ->margins(8, 10, 8, 10)
                ->name($filename)
                ->download()
                ->toResponse($request);
        }

        if ($format === 'print') {
            return response()->view(
                $viewName,
                array_merge($data, ['mode' => 'print']),
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        }

        if ($format === 'html') {
            return response()->view(
                $viewName,
                array_merge($data, ['mode' => 'preview']),
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        }

        abort(404, 'Unsupported format.');
    }
}
