<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Finance\Http\Requests\FinanceActionRequest;
use Modules\Finance\Http\Requests\ListFinanceRequest;
use Modules\Finance\Http\Requests\StoreFinanceAccountRequest;
use Modules\Finance\Http\Requests\StoreJournalEntryRequest;
use Modules\Finance\Http\Resources\FinanceAccountResource;
use Modules\Finance\Http\Resources\JournalEntryResource;
use Modules\Finance\Http\Resources\LedgerEntryResource;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountBalance;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceLedgerEntry;
use Modules\Finance\Services\AccountBalanceService;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Finance\Services\JournalEntryCreationService;
use Modules\Finance\Services\JournalPostingService;
use Modules\Finance\Services\JournalReversalService;
use Modules\Finance\Services\TrialBalanceService;

final class FinanceController
{
    public function accounts(ListFinanceRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(FinanceAccount::query(), $request)->with(['accountType', 'accountCategory', 'parent']);
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }
        foreach (['account_type_id', 'is_active'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return FinanceAccountResource::collection($query->orderBy('code')->paginate($request->perPage()));
    }

    public function createAccount(StoreFinanceAccountRequest $request, ChartOfAccountsService $service): FinanceAccountResource
    {
        return new FinanceAccountResource($service->createAccount($request->toData()));
    }

    public function showAccount(ListFinanceRequest $request, int $account): FinanceAccountResource
    {
        return new FinanceAccountResource($this->scope(FinanceAccount::query(), $request)
            ->with(['accountType', 'accountCategory', 'parent', 'children', 'balances'])->findOrFail($account));
    }

    public function createJournal(StoreJournalEntryRequest $request, JournalEntryCreationService $service): JournalEntryResource
    {
        return new JournalEntryResource($service->create($request->toData()));
    }

    public function postJournal(FinanceActionRequest $request, int $journal, JournalPostingService $service): JsonResponse
    {
        $result = $service->post($this->findJournal($request, $journal), $request->currentUserId());

        return response()->json(['data' => get_object_vars($result)]);
    }

    public function reverseJournal(FinanceActionRequest $request, int $journal, JournalReversalService $service): JournalEntryResource
    {
        $request->validate(['reversal_date' => ['required', 'date']]);

        return new JournalEntryResource($service->reverse(
            $this->findJournal($request, $journal),
            (string) $request->input('reversal_date'),
            $request->currentUserId(),
        ));
    }

    public function ledger(ListFinanceRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(FinanceLedgerEntry::query(), $request)->with(['account', 'journalEntry']);
        if ($request->filled('account_id')) {
            $query->where('account_id', (int) $request->input('account_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->input('date_to'));
        }

        return LedgerEntryResource::collection($query->latest('entry_date')->paginate($request->perPage()));
    }

    public function accountBalance(ListFinanceRequest $request, int $account, AccountBalanceService $service): JsonResponse
    {
        $this->scope(FinanceAccount::query(), $request)->findOrFail($account);
        $query = $this->scope(FinanceAccountBalance::query(), $request)->where('account_id', $account);
        $request->filled('fiscal_period_id')
            ? $query->where('fiscal_period_id', (int) $request->input('fiscal_period_id'))
            : $query->whereNull('fiscal_period_id');
        $balance = $query->with('account')->firstOrFail();

        return response()->json(['data' => get_object_vars($service->result($balance))]);
    }

    public function trialBalance(ListFinanceRequest $request, TrialBalanceService $service): JsonResponse
    {
        $result = $service->calculate(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null,
        );

        return response()->json(['data' => get_object_vars($result)]);
    }

    private function findJournal(FinanceActionRequest $request, int $journal): FinanceJournalEntry
    {
        return $this->scope(FinanceJournalEntry::query(), $request)->findOrFail($journal);
    }

    private function scope(Builder $query, ListFinanceRequest|FinanceActionRequest $request): Builder
    {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }
}
