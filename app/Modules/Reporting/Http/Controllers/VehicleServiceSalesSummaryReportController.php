<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Reporting\Http\Requests\VehicleServiceSalesSummaryReportRequest;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\Reporting\Services\VehicleServiceSalesSummaryReportService;

final class VehicleServiceSalesSummaryReportController
{
    public function __construct(
        private readonly VehicleServiceSalesSummaryReportService $reports,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function index(VehicleServiceSalesSummaryReportRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);

        return response()->json($this->reports->run([
            ...$request->validated(),
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
        ]));
    }
}
