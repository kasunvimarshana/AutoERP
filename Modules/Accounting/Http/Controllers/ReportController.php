<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\ChartOfAccountsService;
use Modules\Accounting\Services\FinancialStatementService;
use Modules\Accounting\Services\TrialBalanceService;
use Modules\Core\Http\Responses\ApiResponse;

class ReportController extends Controller
{
    public function __construct(
        private ChartOfAccountsService $chartOfAccountsService,
        private TrialBalanceService $trialBalanceService,
        private FinancialStatementService $financialStatementService
    ) {}

    /**
     * Get chart of accounts
     */
    public function chartOfAccounts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $chart = $this->chartOfAccountsService->getChartOfAccounts();

        return ApiResponse::success(
            $chart,
            'Chart of accounts retrieved successfully'
        );
    }

    /**
     * Generate trial balance
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $request->validate([
            'organization_id' => ['required', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'include_inactive' => ['nullable', 'boolean'],
            'group_by_type' => ['nullable', 'boolean'],
        ]);

        $organizationId = $request->get('organization_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $includeInactive = $request->boolean('include_inactive', false);
        $groupByType = $request->boolean('group_by_type', false);

        if ($groupByType) {
            $report = $this->trialBalanceService->generateTrialBalanceByType(
                $organizationId,
                $startDate,
                $endDate
            );
        } else {
            $report = $this->trialBalanceService->generateTrialBalance(
                $organizationId,
                $startDate,
                $endDate,
                $includeInactive
            );
        }

        return ApiResponse::success(
            $report,
            'Trial balance generated successfully'
        );
    }

    /**
     * Generate balance sheet
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $request->validate([
            'organization_id' => ['required', 'string'],
            'as_of_date' => ['required', 'date'],
        ]);

        $organizationId = $request->get('organization_id');
        $asOfDate = $request->get('as_of_date');

        $report = $this->financialStatementService->generateBalanceSheet(
            $organizationId,
            $asOfDate
        );

        return ApiResponse::success(
            $report,
            'Balance sheet generated successfully'
        );
    }

    /**
     * Generate income statement
     */
    public function incomeStatement(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $request->validate([
            'organization_id' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $organizationId = $request->get('organization_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $report = $this->financialStatementService->generateIncomeStatement(
            $organizationId,
            $startDate,
            $endDate
        );

        return ApiResponse::success(
            $report,
            'Income statement generated successfully'
        );
    }

    /**
     * Generate cash flow statement
     */
    public function cashFlowStatement(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $request->validate([
            'organization_id' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $organizationId = $request->get('organization_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $report = $this->financialStatementService->generateCashFlowStatement(
            $organizationId,
            $startDate,
            $endDate
        );

        return ApiResponse::success(
            $report,
            'Cash flow statement generated successfully'
        );
    }

    /**
     * Get account ledger
     */
    public function accountLedger(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $request->validate([
            'account_id' => ['required', 'string', 'exists:accounts,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $account = Account::findOrFail($request->get('account_id'));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = $account->journalLines()
            ->with(['journalEntry', 'account'])
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted');

                if ($startDate) {
                    $q->where('entry_date', '>=', $startDate);
                }

                if ($endDate) {
                    $q->where('entry_date', '<=', $endDate);
                }
            })
            ->orderBy('created_at');

        $perPage = $request->get('per_page', 50);
        $lines = $query->paginate($perPage);

        $runningBalance = '0';
        $scale = config('accounting.decimal_scale', 6);

        $ledgerEntries = $lines->getCollection()->map(function ($line) use (&$runningBalance, $scale, $account) {
            if ($account->normal_balance === 'debit') {
                $runningBalance = bcadd($runningBalance, $line->debit, $scale);
                $runningBalance = bcsub($runningBalance, $line->credit, $scale);
            } else {
                $runningBalance = bcadd($runningBalance, $line->credit, $scale);
                $runningBalance = bcsub($runningBalance, $line->debit, $scale);
            }

            return [
                'id' => $line->id,
                'entry_date' => $line->journalEntry->entry_date->toDateString(),
                'entry_number' => $line->journalEntry->entry_number,
                'description' => $line->description ?? $line->journalEntry->description,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'balance' => $runningBalance,
            ];
        });

        return ApiResponse::paginated(
            $lines->setCollection($ledgerEntries),
            'Account ledger retrieved successfully'
        );
    }
}
