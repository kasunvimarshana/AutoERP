<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\CashierReportRequest;
use Modules\Reporting\Services\CashierReportService;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;

final class CashierReportController
{
    public function __construct(
        private readonly CashierReportService $reports,
        private readonly ReportExport $exports,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function index(CashierReportRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);

        $data = $this->reports->run(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated(),
        );

        return response()->json(['data' => $data]);
    }

    public function cashiers(CashierReportRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);

        $cashiers = $this->reports->cashiersLookup(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated('search'),
        );

        return response()->json(['data' => $cashiers]);
    }

    public function export(CashierReportRequest $request, string $format): Response
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_EXPORT);

        return $this->exports->export(
            $format,
            $this->reports->definition(),
            $this->reports->exportRows(
                $request->tenantId(),
                $request->organizationUnitId(),
                $request->validated(),
            ),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated(),
        );
    }
}
