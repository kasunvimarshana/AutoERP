<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\GenerateFinancialReportServiceInterface;

class FinancialReportController extends Controller
{
    public function __construct(private readonly GenerateFinancialReportServiceInterface $reportService) {}

    public function balanceSheet(Request $request): JsonResponse
    {
        $tenantId = (int)$request->query('tenant_id', 1);
        $asOf     = new \DateTimeImmutable($request->query('as_of', 'now'));
        $report   = $this->reportService->balanceSheet($tenantId, $asOf);
        return response()->json($report);
    }

    public function profitAndLoss(Request $request): JsonResponse
    {
        $tenantId = (int)$request->query('tenant_id', 1);
        $from     = new \DateTimeImmutable($request->query('from', date('Y-01-01')));
        $to       = new \DateTimeImmutable($request->query('to', date('Y-12-31')));
        $report   = $this->reportService->profitAndLoss($tenantId, $from, $to);
        return response()->json($report);
    }
}
