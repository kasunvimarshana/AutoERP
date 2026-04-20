<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\FinancialReportServiceInterface;
use Modules\Finance\Domain\Entities\Account;
use Modules\Finance\Domain\Entities\JournalEntry;
use Modules\Finance\Infrastructure\Http\Requests\BalanceSheetRequest;
use Modules\Finance\Infrastructure\Http\Requests\GeneralLedgerRequest;
use Modules\Finance\Infrastructure\Http\Requests\ProfitLossRequest;
use Modules\Finance\Infrastructure\Http\Requests\TrialBalanceRequest;

class FinancialReportController extends AuthorizedController
{
    public function __construct(
        private readonly FinancialReportServiceInterface $reportService,
    ) {}

    public function generalLedger(GeneralLedgerRequest $request): JsonResponse
    {
        $this->authorize('viewAny', JournalEntry::class);

        $validated = $request->validated();
        $tenantId = (int) $validated['tenant_id'];

        $result = $this->reportService->generalLedger($tenantId, $validated);

        return Response::json($result);
    }

    public function trialBalance(TrialBalanceRequest $request): JsonResponse
    {
        $this->authorize('viewAny', JournalEntry::class);

        $validated = $request->validated();
        $tenantId = (int) $validated['tenant_id'];

        $result = $this->reportService->trialBalance($tenantId, $validated);

        return Response::json($result);
    }

    public function balanceSheet(BalanceSheetRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validated();
        $tenantId = (int) $validated['tenant_id'];

        $result = $this->reportService->balanceSheet($tenantId, $validated);

        return Response::json($result);
    }

    public function profitLoss(ProfitLossRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validated();
        $tenantId = (int) $validated['tenant_id'];

        $result = $this->reportService->profitLoss($tenantId, $validated);

        return Response::json($result);
    }
}
