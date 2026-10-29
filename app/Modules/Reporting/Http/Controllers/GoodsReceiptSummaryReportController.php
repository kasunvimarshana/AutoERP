<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\GoodsReceiptSummaryRequest;
use Modules\Reporting\Services\GoodsReceiptSummaryReportService;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;

final class GoodsReceiptSummaryReportController
{
    public function __construct(
        private readonly GoodsReceiptSummaryReportService $report,
        private readonly ReportExport $export,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function index(GoodsReceiptSummaryRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);

        return response()->json($this->report->run($this->input($request)));
    }

    public function export(GoodsReceiptSummaryRequest $request, string $format): Response
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_EXPORT);

        return $this->export->response($format, $this->report->exportData($this->input($request)));
    }

    /** @return array<string, mixed> */
    private function input(GoodsReceiptSummaryRequest $request): array
    {
        return [
            ...$request->validated(),
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
        ];
    }
}
