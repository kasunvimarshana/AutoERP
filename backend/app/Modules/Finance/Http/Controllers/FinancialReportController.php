<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Finance\Services\FinancialReportService;
use App\Modules\Finance\Services\GeneralLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Financial Report API Controller
 *
 * Handles HTTP requests for financial report generation.
 */
class FinancialReportController extends BaseController
{
    public function __construct(
        protected FinancialReportService $financialReportService,
        protected GeneralLedgerService $generalLedgerService
    ) {}

    public function trialBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->financialReportService->generateTrialBalance(
            $validated['start_date'],
            $validated['end_date']
        );

        return $this->successResponse($report, 'Trial balance generated successfully');
    }

    public function profitAndLoss(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->financialReportService->generateProfitAndLoss(
            $validated['start_date'],
            $validated['end_date']
        );

        return $this->successResponse($report, 'Profit and loss statement generated successfully');
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'as_of_date' => 'required|date',
        ]);

        $report = $this->financialReportService->generateBalanceSheet(
            $validated['as_of_date']
        );

        return $this->successResponse($report, 'Balance sheet generated successfully');
    }

    public function accountLedger(Request $request, int $accountId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $ledger = $this->generalLedgerService->getAccountLedger(
            $accountId,
            $validated['start_date'],
            $validated['end_date']
        );

        return $this->successResponse($ledger, 'Account ledger generated successfully');
    }

    public function generalLedger(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $ledger = $this->generalLedgerService->getGeneralLedger(
            $validated['start_date'],
            $validated['end_date']
        );

        return $this->successResponse($ledger, 'General ledger generated successfully');
    }
}
