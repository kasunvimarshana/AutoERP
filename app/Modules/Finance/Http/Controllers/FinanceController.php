<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Finance\Http\Requests\FinanceActionRequest;
use Modules\Finance\Http\Requests\ListFinanceRequest;
use Modules\Finance\Http\Requests\StoreFinanceAccountRequest;
use Modules\Finance\Http\Requests\StoreJournalEntryRequest;
use Modules\Finance\Http\Requests\UpdateFiscalStatusRequest;
use Modules\Finance\Http\Requests\UpsertPostingProfileRequest;
use Modules\Finance\Http\Resources\AccountBalanceReportResource;
use Modules\Finance\Http\Resources\FinanceAccountResource;
use Modules\Finance\Http\Resources\JournalEntryResource;
use Modules\Finance\Http\Resources\LedgerEntryResource;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountCategory;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinanceFiscalPeriod;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceLedgerEntry;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Services\AccountBalanceService;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Finance\Services\FinanceStatementService;
use Modules\Finance\Services\FiscalPeriodService;
use Modules\Finance\Services\JournalEntryCreationService;
use Modules\Finance\Services\JournalPostingService;
use Modules\Finance\Services\JournalReversalService;
use Modules\Finance\Services\PostingProfileService;
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

    public function updateAccount(
        StoreFinanceAccountRequest $request,
        int $account,
        ChartOfAccountsService $service,
    ): FinanceAccountResource {
        return new FinanceAccountResource($service->updateAccount(
            $this->scope(FinanceAccount::query(), $request)->findOrFail($account),
            $request->toData(),
        ));
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

    public function journals(ListFinanceRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(FinanceJournalEntry::query(), $request)
            ->with(['fiscalPeriod', 'postingProfile', 'reversalOf', 'reversals'])
            ->withCount(['lines', 'ledgerEntries']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('journal_number', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('source_number', 'like', "%{$search}%"));
        }
        foreach (['status', 'journal_type', 'source_module', 'source_type', 'source_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        $this->dateRange($query, $request, 'journal_date');

        return JournalEntryResource::collection(
            $query->orderByDesc('journal_date')->orderByDesc('id')->paginate($request->perPage()),
        );
    }

    public function showJournal(ListFinanceRequest $request, int $journal): JournalEntryResource
    {
        return new JournalEntryResource($this->scope(FinanceJournalEntry::query(), $request)
            ->with([
                'fiscalPeriod.fiscalYear',
                'postingProfile',
                'lines.account',
                'lines.dimension',
                'ledgerEntries.account',
                'ledgerEntries.journalLine',
                'ledgerEntries.fiscalPeriod',
                'ledgerEntries.dimension',
                'reversalOf',
                'reversals',
            ])
            ->findOrFail($journal));
    }

    public function updateJournal(
        StoreJournalEntryRequest $request,
        int $journal,
        JournalEntryCreationService $service,
    ): JournalEntryResource {
        return new JournalEntryResource($service->update(
            $this->findJournal($request, $journal),
            $request->toData(),
        ));
    }

    public function cancelJournal(
        FinanceActionRequest $request,
        int $journal,
        JournalEntryCreationService $service,
    ): JournalEntryResource {
        return new JournalEntryResource($service->cancel($this->findJournal($request, $journal)));
    }

    public function postJournal(FinanceActionRequest $request, int $journal, JournalPostingService $service): JsonResponse
    {
        $result = $service->post($this->findJournal($request, $journal), $request->currentUserId());

        return response()->json(['data' => get_object_vars($result)]);
    }

    public function reverseJournal(FinanceActionRequest $request, int $journal, JournalReversalService $service): JournalEntryResource
    {
        $request->validate([
            'reversal_date' => ['required', 'date'],
            'reversal_reason' => ['required', 'string', 'max:1000'],
        ]);

        return new JournalEntryResource($service->reverse(
            $this->findJournal($request, $journal),
            (string) $request->input('reversal_date'),
            $request->currentUserId(),
            (string) $request->input('reversal_reason'),
        ));
    }

    public function ledger(ListFinanceRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(FinanceLedgerEntry::query(), $request)
            ->with(['account', 'journalEntry', 'journalLine', 'fiscalPeriod', 'dimension']);
        if ($request->filled('account_id')) {
            $query->where('account_id', (int) $request->input('account_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->input('date_to'));
        }
        foreach (['source_module', 'source_type', 'source_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return LedgerEntryResource::collection(
            $query->orderByDesc('entry_date')->orderByDesc('id')->paginate($request->perPage()),
        );
    }

    public function accountBalance(ListFinanceRequest $request, int $account, AccountBalanceService $service): JsonResponse
    {
        $this->scope(FinanceAccount::query(), $request)->findOrFail($account);
        $model = $this->scope(FinanceAccount::query(), $request)->findOrFail($account);
        [$dateFrom, $dateTo] = $this->reportDates($request);

        return response()->json([
            'data' => (new AccountBalanceReportResource(
                $service->forDateRange($model, $dateFrom, $dateTo),
            ))->resolve($request),
        ]);
    }

    public function accountBalances(ListFinanceRequest $request, AccountBalanceService $service): AnonymousResourceCollection
    {
        $query = $this->scope(FinanceAccount::query(), $request);
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }
        [$dateFrom, $dateTo] = $this->reportDates($request);
        $paginator = $query->orderBy('code')->paginate($request->perPage());
        $paginator->setCollection(collect($service->forAccounts($paginator->getCollection(), $dateFrom, $dateTo)));

        return AccountBalanceReportResource::collection($paginator);
    }

    public function trialBalance(ListFinanceRequest $request, TrialBalanceService $service): JsonResponse
    {
        $result = $service->calculate(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null,
            $request->filled('date_from') ? (string) $request->input('date_from') : null,
            $request->filled('date_to') ? (string) $request->input('date_to') : null,
        );

        return response()->json(['data' => get_object_vars($result)]);
    }

    public function profitAndLoss(ListFinanceRequest $request, FinanceStatementService $service): JsonResponse
    {
        return response()->json(['data' => $service->profitAndLoss(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('date_from') ? (string) $request->input('date_from') : null,
            $request->filled('date_to') ? (string) $request->input('date_to') : null,
        )]);
    }

    public function balanceSheet(ListFinanceRequest $request, FinanceStatementService $service): JsonResponse
    {
        return response()->json(['data' => $service->balanceSheet(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('date_to') ? (string) $request->input('date_to') : null,
        )]);
    }

    public function lookups(ListFinanceRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $organizationUnitId = $request->organizationUnitId();

        $types = FinanceAccountType::query()
            ->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'normal_balance', 'statement_type']);
        $categories = FinanceAccountCategory::query()
            ->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'account_type_id', 'code', 'name']);
        $accounts = $this->scope(FinanceAccount::query(), $request)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'is_posting_account', 'is_active']);
        $periods = $this->scope(FinanceFiscalPeriod::query(), $request)
            ->with('fiscalYear:id,name,status')
            ->orderByDesc('start_date')
            ->get();
        $profiles = FinancePostingProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when(
                $organizationUnitId === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $organizationUnitId),
            )
            ->with('rules.account:id,code,name')
            ->orderBy('code')
            ->get();

        return response()->json(['data' => compact('types', 'categories', 'accounts', 'periods', 'profiles')]);
    }

    public function postingProfiles(ListFinanceRequest $request): AnonymousResourceCollection
    {
        return JsonResource::collection(
            $this->scope(FinancePostingProfile::query(), $request)
                ->with('rules.account')
                ->orderBy('code')
                ->paginate($request->perPage()),
        );
    }

    public function createPostingProfile(
        UpsertPostingProfileRequest $request,
        PostingProfileService $service,
    ): JsonResponse {
        return response()->json(['data' => $service->save(
            $request->tenantId(),
            $request->organizationUnitId(),
            (string) $request->input('code'),
            (string) $request->input('name'),
            $request->filled('description') ? (string) $request->input('description') : null,
            $request->boolean('is_active', true),
            $request->input('rules'),
        )]);
    }

    public function updatePostingProfile(
        UpsertPostingProfileRequest $request,
        int $profile,
        PostingProfileService $service,
    ): JsonResponse {
        $model = $this->scope(FinancePostingProfile::query(), $request)->findOrFail($profile);

        return response()->json(['data' => $service->save(
            $request->tenantId(),
            $request->organizationUnitId(),
            (string) $request->input('code'),
            (string) $request->input('name'),
            $request->filled('description') ? (string) $request->input('description') : null,
            $request->boolean('is_active', true),
            $request->input('rules'),
            $model,
        )]);
    }

    public function fiscalYears(ListFinanceRequest $request): AnonymousResourceCollection
    {
        return JsonResource::collection(
            $this->scope(FinanceFiscalYear::query(), $request)
                ->with('periods')
                ->orderByDesc('start_date')
                ->paginate($request->perPage()),
        );
    }

    public function fiscalPeriods(ListFinanceRequest $request): AnonymousResourceCollection
    {
        return JsonResource::collection(
            $this->scope(FinanceFiscalPeriod::query(), $request)
                ->with('fiscalYear')
                ->orderByDesc('start_date')
                ->paginate($request->perPage()),
        );
    }

    public function updateFiscalYearStatus(
        UpdateFiscalStatusRequest $request,
        int $year,
        FiscalPeriodService $service,
    ): JsonResponse {
        $model = $this->scope(FinanceFiscalYear::query(), $request)->findOrFail($year);

        return response()->json(['data' => $service->changeYearStatus($model, $request->status())]);
    }

    public function updateFiscalPeriodStatus(
        UpdateFiscalStatusRequest $request,
        int $period,
        FiscalPeriodService $service,
    ): JsonResponse {
        $model = $this->scope(FinanceFiscalPeriod::query(), $request)->with('fiscalYear')->findOrFail($period);

        return response()->json(['data' => $service->changePeriodStatus($model, $request->status())]);
    }

    private function findJournal(
        FinanceActionRequest|StoreJournalEntryRequest $request,
        int $journal,
    ): FinanceJournalEntry {
        return $this->scope(FinanceJournalEntry::query(), $request)->findOrFail($journal);
    }

    private function scope(
        Builder $query,
        ListFinanceRequest|FinanceActionRequest|StoreFinanceAccountRequest|StoreJournalEntryRequest|UpdateFiscalStatusRequest|UpsertPostingProfileRequest $request,
    ): Builder {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }

    private function dateRange(Builder $query, ListFinanceRequest $request, string $column): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate($column, '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate($column, '<=', $request->input('date_to'));
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function reportDates(ListFinanceRequest $request): array
    {
        if ($request->filled('fiscal_period_id')) {
            $period = $this->scope(FinanceFiscalPeriod::query(), $request)
                ->findOrFail((int) $request->input('fiscal_period_id'));

            return [$period->start_date->toDateString(), $period->end_date->toDateString()];
        }

        return [
            $request->filled('date_from') ? (string) $request->input('date_from') : null,
            $request->filled('date_to') ? (string) $request->input('date_to') : null,
        ];
    }
}
