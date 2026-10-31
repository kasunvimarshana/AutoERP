<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Modules\Reporting\Http\Requests\StockBalanceReportRequest;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\Reporting\Services\StockBalanceReportService;

final class StockBalanceReportController
{
    public function __construct(
        private readonly StockBalanceReportService $report,
        private readonly ReportExport $export,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function index(StockBalanceReportRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);

        return response()->json($this->report->run($this->input($request)));
    }

    public function details(StockBalanceReportRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);
        foreach (['item_id', 'warehouse_id'] as $key) {
            if (! $request->filled($key)) {
                throw ValidationException::withMessages([$key => 'Select a stock row to view its breakdown.']);
            }
        }

        return response()->json(['data' => $this->report->details($this->input($request))]);
    }

    public function export(StockBalanceReportRequest $request, string $format): Response
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_EXPORT);

        return $this->export->response($format, $this->report->exportData($this->input($request)));
    }

    /** @return array<string, mixed> */
    private function input(StockBalanceReportRequest $request): array
    {
        return [
            ...$request->validated(),
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
        ];
    }
}
