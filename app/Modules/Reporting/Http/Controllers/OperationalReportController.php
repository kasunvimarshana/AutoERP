<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\OperationalReportRequest;
use Modules\Reporting\Services\DetailedPurchaseReportService;
use Modules\Reporting\Services\DetailedVehicleServiceReportService;
use Modules\Reporting\Services\EmployeeIncentiveReportService;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;

final class OperationalReportController
{
    public function __construct(
        private readonly DetailedPurchaseReportService $purchase,
        private readonly DetailedVehicleServiceReportService $vehicleService,
        private readonly EmployeeIncentiveReportService $incentives,
        private readonly ReportExport $export,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function detailedPurchase(OperationalReportRequest $request): JsonResponse
    {
        $this->assertView($request);

        return response()->json($this->purchase->run($this->input($request)));
    }

    public function exportDetailedPurchase(OperationalReportRequest $request, string $format): Response
    {
        $this->assertExport($request);
        $input = $this->input($request);

        return $this->export->export(
            $format,
            $this->purchase->definition(),
            $this->purchase->exportRows($input)->all(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $input,
        );
    }

    public function detailedVehicleService(OperationalReportRequest $request): JsonResponse
    {
        $this->assertView($request);

        return response()->json($this->vehicleService->run($this->input($request)));
    }

    public function exportDetailedVehicleService(OperationalReportRequest $request, string $format): Response
    {
        $this->assertExport($request);
        $input = $this->input($request);

        return $this->export->export(
            $format,
            $this->vehicleService->definition(),
            $this->vehicleService->exportRows($input)->all(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $input,
        );
    }

    public function employeeIncentives(OperationalReportRequest $request): JsonResponse
    {
        $this->assertView($request);

        return response()->json($this->incentives->run($this->input($request)));
    }

    public function exportEmployeeIncentives(OperationalReportRequest $request, string $format): Response
    {
        $this->assertExport($request);
        $input = $this->input($request);

        return $this->export->export(
            $format,
            $this->incentives->definition(),
            $this->incentives->exportRows($input)->all(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $input,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function input(OperationalReportRequest $request): array
    {
        return [
            ...$request->validated(),
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
        ];
    }

    private function assertView(OperationalReportRequest $request): void
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            ReportingAuthorizationService::REPORTS_VIEW,
        );
    }

    private function assertExport(OperationalReportRequest $request): void
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            ReportingAuthorizationService::REPORTS_EXPORT,
        );
    }
}
