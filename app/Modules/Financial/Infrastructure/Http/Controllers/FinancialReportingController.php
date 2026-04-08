<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Financial\Application\Contracts\FinancialReportingServiceInterface;

class FinancialReportingController extends AuthorizedController
{
    public function __construct(
        private readonly FinancialReportingServiceInterface $reportingService,
    ) {}

    /**
     * GET /api/financial/reports/balance-sheet
     *
     * Query params:
     *   - tenant_id  (required)
     *   - as_of_date (required, Y-m-d)
     *   - currency   (optional, default USD)
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        $tenantId  = (int) $request->input('tenant_id');
        $asOfDate  = (string) $request->input('as_of_date', now()->toDateString());
        $currency  = (string) $request->input('currency', 'USD');

        $report = $this->reportingService->balanceSheet($tenantId, $asOfDate, $currency);

        return response()->json(['data' => $report]);
    }

    /**
     * GET /api/financial/reports/profit-and-loss
     *
     * Query params:
     *   - tenant_id  (required)
     *   - from_date  (required, Y-m-d)
     *   - to_date    (required, Y-m-d)
     *   - currency   (optional, default USD)
     */
    public function profitAndLoss(Request $request): JsonResponse
    {
        $tenantId = (int) $request->input('tenant_id');
        $fromDate = (string) $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate   = (string) $request->input('to_date', now()->toDateString());
        $currency = (string) $request->input('currency', 'USD');

        $report = $this->reportingService->profitAndLoss($tenantId, $fromDate, $toDate, $currency);

        return response()->json(['data' => $report]);
    }
}
