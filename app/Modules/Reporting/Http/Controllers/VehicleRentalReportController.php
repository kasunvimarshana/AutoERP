<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\VehicleRentalReportRequest;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\Reporting\Services\VehicleRentalReportService;

final class VehicleRentalReportController
{
    public function __construct(
        private readonly VehicleRentalReportService $reports,
        private readonly ReportExport $export,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function runningChart(VehicleRentalReportRequest $request): JsonResponse
    {
        return $this->run($request, VehicleRentalReportService::RUNNING_CHART);
    }

    public function chartExceptions(VehicleRentalReportRequest $request): JsonResponse
    {
        return $this->run($request, VehicleRentalReportService::CHART_EXCEPTIONS);
    }

    public function customerInvoices(VehicleRentalReportRequest $request): JsonResponse
    {
        return $this->run($request, VehicleRentalReportService::CUSTOMER_INVOICES);
    }

    public function ownerVouchers(VehicleRentalReportRequest $request): JsonResponse
    {
        return $this->run($request, VehicleRentalReportService::OWNER_VOUCHERS);
    }

    public function rentalHistory(VehicleRentalReportRequest $request): JsonResponse
    {
        return $this->run($request, VehicleRentalReportService::RENTAL_HISTORY);
    }

    public function exportRunningChart(VehicleRentalReportRequest $request, string $format): Response
    {
        return $this->export($request, VehicleRentalReportService::RUNNING_CHART, $format);
    }

    public function exportChartExceptions(VehicleRentalReportRequest $request, string $format): Response
    {
        return $this->export($request, VehicleRentalReportService::CHART_EXCEPTIONS, $format);
    }

    public function exportCustomerInvoices(VehicleRentalReportRequest $request, string $format): Response
    {
        return $this->export($request, VehicleRentalReportService::CUSTOMER_INVOICES, $format);
    }

    public function exportOwnerVouchers(VehicleRentalReportRequest $request, string $format): Response
    {
        return $this->export($request, VehicleRentalReportService::OWNER_VOUCHERS, $format);
    }

    public function exportRentalHistory(VehicleRentalReportRequest $request, string $format): Response
    {
        return $this->export($request, VehicleRentalReportService::RENTAL_HISTORY, $format);
    }

    private function run(VehicleRentalReportRequest $request, string $key): JsonResponse
    {
        $this->assertView($request);

        return response()->json($this->reports->run($key, $this->input($request)));
    }

    private function export(VehicleRentalReportRequest $request, string $key, string $format): Response
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            ReportingAuthorizationService::REPORTS_EXPORT,
        );
        $input = $this->input($request);

        return $this->export->export(
            $format,
            $this->reports->definition($key),
            $this->reports->exportRows($key, $input)->all(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $input,
        );
    }

    /** @return array<string, mixed> */
    private function input(VehicleRentalReportRequest $request): array
    {
        return [
            ...$request->validated(),
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
        ];
    }

    private function assertView(VehicleRentalReportRequest $request): void
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            ReportingAuthorizationService::REPORTS_VIEW,
        );
    }
}
