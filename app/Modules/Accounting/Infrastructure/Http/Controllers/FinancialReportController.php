<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\GenerateFinancialReportServiceInterface;
class FinancialReportController extends Controller
{
    public function __construct(
        private readonly GenerateFinancialReportServiceInterface $reportService,
    ) {}
    public function balanceSheet(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $asOf     = $request->query('as_of', now()->toDateString());
        $report   = $this->reportService->generateBalanceSheet($tenantId, $asOf);
        return response()->json($report);
    }
    public function profitAndLoss(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $from     = $request->query('from', now()->startOfYear()->toDateString());
        $to       = $request->query('to', now()->toDateString());
        $report   = $this->reportService->generateProfitAndLoss($tenantId, $from, $to);
        return response()->json($report);
    }
}
