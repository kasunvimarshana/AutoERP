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
use Modules\Finance\Http\Resources\PostingProfileResource;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountCategory;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinanceBankReconciliation;
use Modules\Finance\Models\FinanceBankStatementLine;
use Modules\Finance\Models\FinanceBudget;
use Modules\Finance\Models\FinanceFiscalPeriod;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Services\AccountBalanceService;
use Modules\Finance\Services\AgingReportService;
use Modules\Finance\Services\BankReconciliationService;
use Modules\Finance\Services\BudgetService;
use Modules\Finance\Services\CashFlowReportService;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Finance\Services\CurrencyRevaluationService;
use Modules\Finance\Services\FinanceStatementService;
use Modules\Finance\Services\FinanceTaxReportService;
use Modules\Finance\Services\FiscalPeriodService;
use Modules\Finance\Services\GeneralLedgerReportService;
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

    public function ledger(ListFinanceRequest $request, GeneralLedgerReportService $service): AnonymousResourceCollection
    {
        return LedgerEntryResource::collection(
            $service->paginate(
                $request->tenantId(),
                $request->organizationUnitId(),
                $this->filters($request, ['account_id', 'date_from', 'date_to', 'source_module', 'source_type', 'source_id']),
                $request->perPage(),
            ),
        );
    }

    public function accountBalance(ListFinanceRequest $request, int $account, AccountBalanceService $service): JsonResponse
    {
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

    public function cashFlow(ListFinanceRequest $request, CashFlowReportService $service): JsonResponse
    {
        [$dateFrom, $dateTo] = $this->reportDates($request);

        return response()->json(['data' => $service->calculate(
            $request->tenantId(),
            $request->organizationUnitId(),
            $dateFrom,
            $dateTo,
        )]);
    }

    public function arAging(ListFinanceRequest $request, AgingReportService $service): JsonResponse
    {
        return response()->json(['data' => $service->receivables(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('date_to') ? (string) $request->input('date_to') : null,
        )]);
    }

    public function apAging(ListFinanceRequest $request, AgingReportService $service): JsonResponse
    {
        return response()->json(['data' => $service->payables(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('date_to') ? (string) $request->input('date_to') : null,
        )]);
    }

    public function taxLiability(ListFinanceRequest $request, FinanceTaxReportService $service): JsonResponse
    {
        [$dateFrom, $dateTo] = $this->reportDates($request);

        return response()->json(['data' => $service->liability(
            $request->tenantId(),
            $request->organizationUnitId(),
            $dateFrom,
            $dateTo,
        )]);
    }

    public function taxReconciliation(ListFinanceRequest $request, FinanceTaxReportService $service): JsonResponse
    {
        [$dateFrom, $dateTo] = $this->reportDates($request);

        return response()->json(['data' => $service->reconciliation(
            $request->tenantId(),
            $request->organizationUnitId(),
            $dateFrom,
            $dateTo,
        )]);
    }

    public function postCurrencyRevaluation(
        FinanceActionRequest $request,
        CurrencyRevaluationService $service,
    ): JsonResponse {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'exposure_type' => ['required', 'string', 'max:60'],
            'source_id' => ['required', 'integer', 'min:1'],
            'posting_date' => ['required', 'date'],
            'posting_profile' => ['required', 'string', 'max:100'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'gain_profile_key' => ['nullable', 'string', 'max:100'],
            'loss_profile_key' => ['nullable', 'string', 'max:100'],
            'exposures' => ['required', 'array', 'min:1'],
            'exposures.*.exposure_key' => ['required', 'string', 'max:100'],
            'exposures.*.carrying_amount' => ['required', 'decimal:0,6'],
            'exposures.*.revalued_amount' => ['required', 'decimal:0,6'],
            'exposures.*.description' => ['nullable', 'string', 'max:255'],
            'exposures.*.source_line_type' => ['nullable', 'string', 'max:100'],
            'exposures.*.source_line_id' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json(['data' => get_object_vars($service->revalue(
            $request->tenantId(),
            $request->organizationUnitId(),
            (string) $data['exposure_type'],
            (int) $data['source_id'],
            (string) $data['posting_date'],
            (string) $data['posting_profile'],
            $data['exposures'],
            isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            (string) ($data['exchange_rate'] ?? '1.000000'),
            $request->currentUserId(),
            (string) ($data['gain_profile_key'] ?? 'unrealized_gain'),
            (string) ($data['loss_profile_key'] ?? 'unrealized_loss'),
        ))]);
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

        $dimensions = $this->scope(\Modules\Finance\Models\FinanceDimension::query(), $request)
            ->where('is_active', true)
            ->orderBy('dimension_type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'dimension_type']);
        $bankAccounts = $this->scope(FinanceAccount::query(), $request)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'is_posting_account', 'is_active']);

        return response()->json(['data' => compact('types', 'categories', 'accounts', 'periods', 'profiles', 'dimensions', 'bankAccounts')]);
    }

    public function postingProfiles(ListFinanceRequest $request): AnonymousResourceCollection
    {
        return PostingProfileResource::collection(
            $this->scope(FinancePostingProfile::query(), $request)
                ->with('rules.account')
                ->orderBy('code')
                ->paginate($request->perPage()),
        );
    }

    public function createPostingProfile(
        UpsertPostingProfileRequest $request,
        PostingProfileService $service,
    ): PostingProfileResource {
        return new PostingProfileResource($service->save(
            $request->tenantId(),
            $request->organizationUnitId(),
            (string) $request->input('code'),
            (string) $request->input('name'),
            $request->filled('description') ? (string) $request->input('description') : null,
            $request->boolean('is_active', true),
            $request->input('rules'),
        ));
    }

    public function updatePostingProfile(
        UpsertPostingProfileRequest $request,
        int $profile,
        PostingProfileService $service,
    ): PostingProfileResource {
        $model = $this->scope(FinancePostingProfile::query(), $request)->findOrFail($profile);

        return new PostingProfileResource($service->save(
            $request->tenantId(),
            $request->organizationUnitId(),
            (string) $request->input('code'),
            (string) $request->input('name'),
            $request->filled('description') ? (string) $request->input('description') : null,
            $request->boolean('is_active', true),
            $request->input('rules'),
            $model,
        ));
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
        $query = $this->scope(FinanceFiscalPeriod::query(), $request)
            ->with('fiscalYear');
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        return JsonResource::collection(
            $query->orderByDesc('start_date')->paginate($request->perPage()),
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

    public function bankReconciliations(ListFinanceRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(FinanceBankReconciliation::query(), $request)
            ->with('bankAccount');
        if ($request->filled('account_id')) {
            $query->where('bank_account_id', (int) $request->input('account_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        $this->dateRange($query, $request, 'statement_date');

        return JsonResource::collection(
            $query->orderByDesc('statement_date')->orderByDesc('id')->paginate($request->perPage()),
        );
    }

    public function showBankReconciliation(
        ListFinanceRequest $request,
        int $reconciliation,
        BankReconciliationService $service,
    ): JsonResponse {
        $model = $this->scope(FinanceBankReconciliation::query(), $request)
            ->with(['bankAccount', 'statementLines.matchedLedgerEntry'])
            ->findOrFail($reconciliation);

        return response()->json(['data' => $service->report($model)]);
    }

    public function createBankReconciliation(
        FinanceActionRequest $request,
        BankReconciliationService $service,
    ): JsonResponse {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'bank_account_id' => ['required', 'integer', 'min:1', 'exists:finance_accounts,id'],
            'statement_reference' => ['required', 'string', 'max:150'],
            'statement_date' => ['required', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'opening_balance' => ['nullable', 'decimal:0,6'],
            'closing_balance' => ['nullable', 'decimal:0,6'],
            'notes' => ['nullable', 'string'],
            'statement_lines' => ['nullable', 'array'],
            'statement_lines.*.statement_date' => ['nullable', 'date'],
            'statement_lines.*.reference' => ['nullable', 'string', 'max:150'],
            'statement_lines.*.description' => ['nullable', 'string', 'max:255'],
            'statement_lines.*.debit' => ['nullable', 'decimal:0,6', 'gte:0'],
            'statement_lines.*.credit' => ['nullable', 'decimal:0,6', 'gte:0'],
        ]);

        $reconciliation = $service->create(
            $request->tenantId(),
            $request->organizationUnitId(),
            (int) $data['bank_account_id'],
            (string) $data['statement_reference'],
            (string) $data['statement_date'],
            (string) ($data['opening_balance'] ?? '0.000000'),
            (string) ($data['closing_balance'] ?? '0.000000'),
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['notes'] ?? null,
            $data['statement_lines'] ?? [],
        );

        return response()->json(['data' => $service->report($reconciliation)]);
    }

    public function matchBankStatementLine(
        FinanceActionRequest $request,
        int $reconciliation,
        int $line,
        BankReconciliationService $service,
    ): JsonResponse {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'ledger_entry_id' => ['required', 'integer', 'min:1', 'exists:finance_ledger_entries,id'],
        ]);
        $model = $this->scope(FinanceBankStatementLine::query(), $request)
            ->where('reconciliation_id', $reconciliation)
            ->findOrFail($line);

        return response()->json(['data' => $service->matchLine($model, (int) $data['ledger_entry_id'])]);
    }

    public function unmatchBankStatementLine(
        FinanceActionRequest $request,
        int $reconciliation,
        int $line,
        BankReconciliationService $service,
    ): JsonResponse {
        $model = $this->scope(FinanceBankStatementLine::query(), $request)
            ->where('reconciliation_id', $reconciliation)
            ->findOrFail($line);

        return response()->json(['data' => $service->unmatchLine($model)]);
    }

    public function completeBankReconciliation(
        FinanceActionRequest $request,
        int $reconciliation,
        BankReconciliationService $service,
    ): JsonResponse {
        $model = $this->scope(FinanceBankReconciliation::query(), $request)->findOrFail($reconciliation);

        return response()->json(['data' => $service->report($service->complete($model, $request->currentUserId()))]);
    }

    public function budgets(ListFinanceRequest $request): AnonymousResourceCollection
    {
        return JsonResource::collection(
            $this->scope(FinanceBudget::query(), $request)
                ->withCount('lines')
                ->orderByDesc('budget_year')
                ->orderBy('name')
                ->paginate($request->perPage()),
        );
    }

    public function showBudget(
        ListFinanceRequest $request,
        int $budget,
    ): JsonResource {
        return new JsonResource($this->scope(FinanceBudget::query(), $request)
            ->with(['lines.account', 'lines.fiscalPeriod', 'lines.dimension'])
            ->findOrFail($budget));
    }

    public function createBudget(FinanceActionRequest $request, BudgetService $service): JsonResponse
    {
        $data = $request->validate($this->budgetRules());

        return response()->json(['data' => $service->save(
            $request->tenantId(),
            $request->organizationUnitId(),
            (int) $data['budget_year'],
            (string) $data['name'],
            $data['lines'],
            isset($data['fiscal_year_id']) ? (int) $data['fiscal_year_id'] : null,
            (string) ($data['status'] ?? 'draft'),
            $data['description'] ?? null,
        )->load(['lines.account', 'lines.fiscalPeriod', 'lines.dimension'])]);
    }

    public function updateBudget(
        FinanceActionRequest $request,
        int $budget,
        BudgetService $service,
    ): JsonResponse {
        $data = $request->validate($this->budgetRules());
        $model = $this->scope(FinanceBudget::query(), $request)->findOrFail($budget);

        return response()->json(['data' => $service->save(
            $request->tenantId(),
            $request->organizationUnitId(),
            (int) $data['budget_year'],
            (string) $data['name'],
            $data['lines'],
            isset($data['fiscal_year_id']) ? (int) $data['fiscal_year_id'] : null,
            (string) ($data['status'] ?? 'draft'),
            $data['description'] ?? null,
            $model,
        )->load(['lines.account', 'lines.fiscalPeriod', 'lines.dimension'])]);
    }

    public function budgetActuals(
        ListFinanceRequest $request,
        int $budget,
        BudgetService $service,
    ): JsonResponse {
        $model = $this->scope(FinanceBudget::query(), $request)
            ->with(['lines.account', 'lines.fiscalPeriod'])
            ->findOrFail($budget);

        return response()->json(['data' => $service->actualVsBudget($model)]);
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
     * @param  list<string>  $names
     * @return array<string, mixed>
     */
    private function filters(ListFinanceRequest $request, array $names): array
    {
        $filters = [];

        foreach ($names as $name) {
            if ($request->filled($name)) {
                $filters[$name] = $request->input($name);
            }
        }

        return $filters;
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

    /**
     * @return array<string, mixed>
     */
    private function budgetRules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'fiscal_year_id' => ['nullable', 'integer', 'min:1', 'exists:finance_fiscal_years,id'],
            'budget_year' => ['required', 'integer', 'between:1900,2200'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'min:1', 'exists:finance_accounts,id'],
            'lines.*.fiscal_period_id' => ['nullable', 'integer', 'min:1', 'exists:finance_fiscal_periods,id'],
            'lines.*.dimension_id' => ['nullable', 'integer', 'min:1', 'exists:finance_dimensions,id'],
            'lines.*.budget_month' => ['nullable', 'integer', 'between:1,12'],
            'lines.*.amount' => ['required', 'decimal:0,6', 'gte:0'],
        ];
    }
}
