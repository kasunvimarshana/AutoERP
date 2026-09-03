<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Reporting\Http\Requests\GrnPayablesReportRequest;
use Modules\Reporting\Http\Requests\OperationalReportRequest;
use Modules\Reporting\Http\Requests\SummaryReportRequest;
use Modules\Reporting\Services\DetailedPurchaseReportService;
use Modules\Reporting\Services\DetailedVehicleServiceReportService;
use Modules\Reporting\Services\EmployeeIncentiveReportService;
use Modules\Reporting\Services\GrnPayablesReportService;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\Reporting\Services\SummaryReportService;

final class OperationalReportController
{
    public function __construct(
        private readonly DetailedPurchaseReportService $purchase,
        private readonly GrnPayablesReportService $grnPayables,
        private readonly DetailedVehicleServiceReportService $vehicleService,
        private readonly EmployeeIncentiveReportService $incentives,
        private readonly SummaryReportService $summary,
        private readonly ReportExport $export,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function summary(SummaryReportRequest $request): JsonResponse
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            ReportingAuthorizationService::REPORTS_VIEW,
        );
        $validated = $request->validated();

        return response()->json([
            'data' => $this->summary->run(
                $request->tenantId(),
                $request->organizationUnitId(),
                (string) $validated['date_from'],
                (string) $validated['date_to'],
            ),
        ]);
    }

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

    public function grnPayables(GrnPayablesReportRequest $request): JsonResponse
    {
        $this->assertView($request);

        return response()->json($this->grnPayables->run($this->grnPayablesInput($request)));
    }

    public function exportGrnPayables(GrnPayablesReportRequest $request, string $format): Response
    {
        $this->assertExport($request);
        $input = $this->grnPayablesInput($request);

        return $this->export->export(
            $format,
            $this->grnPayables->definition(),
            $this->grnPayables->exportRows($input)->all(),
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

    /** @return array<string, mixed> */
    private function grnPayablesInput(GrnPayablesReportRequest $request): array
    {
        return [
            ...$request->validated(),
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
        ];
    }

    private function assertView(TenantScopedRequest $request): void
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            ReportingAuthorizationService::REPORTS_VIEW,
        );
    }

    private function assertExport(TenantScopedRequest $request): void
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            ReportingAuthorizationService::REPORTS_EXPORT,
        );
    }
}
