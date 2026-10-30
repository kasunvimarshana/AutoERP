<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Expense\Enums\ExpenseStatus;
use Modules\Finance\Enums\JournalType;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceLedgerEntry;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;

final class ExpenseReportService
{
    public const REPORT_KEY = 'expenses';

    private const EVENT_POSTED = 'posted';

    private const EVENT_REVERSAL = 'reversal';

    private const SOURCE_PAID = 'paid';

    private const SOURCE_CONSUMABLE = 'consumable';

    private const SOURCE_OTHER = 'other';

    private const EXPENSE_MODULE = 'expense';

    private const CONSUMABLE_MODULE = 'inventory';

    private const CONSUMABLE_TYPE = 'internal_consumption';

    private const ZERO = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly OperationalReportResponseBuilder $responses,
        private readonly ReportBrandingResolver $branding,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function run(array $params): array
    {
        $events = $this->events($params);
        $overview = $this->analytics(clone $events);
        $this->sort($events, $params);

        $response = $this->responses->paginate(
            $events,
            fn (object $row): array => $this->row($row),
            $this->definition(),
            $overview['summary'],
            max(1, (int) ($params['page'] ?? 1)),
            min(100, max(1, (int) ($params['per_page'] ?? 25))),
        );
        $organizationUnitId = $this->organizationUnitId($params);
        $brand = $this->branding->resolve((int) $params['tenant_id'], $organizationUnitId);

        return [
            ...$response,
            'currency_code' => (string) ($brand['currency_code'] ?? ''),
            'period' => [
                'date_from' => (string) $params['date_from'],
                'date_to' => (string) $params['date_to'],
            ],
            'by_expense_type' => $overview['by_expense_type'],
            'by_payment_method' => $overview['by_payment_method'],
            'by_source' => $overview['by_source'],
            'by_consumable_item' => $overview['by_consumable_item'],
            'by_other_account' => $overview['by_other_account'],
            'trend' => $overview['trend'],
            'filter_options' => $this->filterOptions((int) $params['tenant_id'], $organizationUnitId),
            'basis' => 'Amounts come from posted Finance ledger entries in Profit and Loss expense accounts. Reversals affect the date they were posted. Expense types and payment methods apply only to paid expenses; consumables are grouped by item.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
    ): array {
        return $this->analytics($this->events([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    public function exportRows(array $params): Collection
    {
        $events = $this->events($params);
        $this->sort($events, $params);

        return $this->responses->exportRows($events, fn (object $row): array => $this->row($row));
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: self::REPORT_KEY,
            title: 'Overall Expense Report',
            group: 'Finance',
            model: FinanceLedgerEntry::class,
            columns: [
                new ReportColumn('event_date', 'Date', sortBy: 'event_date', format: 'date'),
                new ReportColumn('event_type', 'Activity', sortBy: 'event_type', format: 'enum'),
                new ReportColumn('source', 'Source', sortBy: 'source'),
                new ReportColumn('document', 'Document', sortBy: 'document'),
                new ReportColumn('detail', 'Type / item / account', sortBy: 'detail'),
                new ReportColumn('paid_through', 'Paid through', sortBy: 'paid_through'),
                new ReportColumn('reference', 'Reference'),
                new ReportColumn('finance_reference', 'Finance reference', format: 'link', hrefPath: 'finance_reference_url'),
                new ReportColumn('net_amount', 'Net amount', sortBy: 'net_amount', format: 'money', summarize: true),
            ],
            dateColumn: 'event_date',
            defaultSort: 'event_date',
            defaultDirection: 'desc',
            description: 'All Profit and Loss expense postings, including paid expenses, consumable usage, and other Finance entries.',
            orientation: 'landscape',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function events(array $params): Builder
    {
        $ledger = DB::table('finance_ledger_entries as ledger')
            ->join('finance_journal_entries as journals', function ($join): void {
                $join->on('journals.id', '=', 'ledger.journal_entry_id')
                    ->on('journals.tenant_id', '=', 'ledger.tenant_id');
            })
            ->join('finance_accounts as accounts', function ($join): void {
                $join->on('accounts.id', '=', 'ledger.account_id')
                    ->on('accounts.tenant_id', '=', 'ledger.tenant_id');
            })
            ->join('finance_account_types as account_types', function ($join): void {
                $join->on('account_types.id', '=', 'accounts.account_type_id')
                    ->on('account_types.tenant_id', '=', 'ledger.tenant_id');
            })
            ->leftJoin('expenses', function ($join): void {
                $join->on('expenses.id', '=', 'ledger.source_id')
                    ->on('expenses.tenant_id', '=', 'ledger.tenant_id')
                    ->where('ledger.source_module', self::EXPENSE_MODULE)
                    ->where('ledger.source_type', self::EXPENSE_MODULE);
            })
            ->leftJoin('inventory_movements as movements', function ($join): void {
                $join->on('movements.id', '=', 'ledger.source_id')
                    ->on('movements.tenant_id', '=', 'ledger.tenant_id')
                    ->where('ledger.source_module', self::CONSUMABLE_MODULE)
                    ->where('ledger.source_type', self::CONSUMABLE_TYPE);
            })
            ->leftJoin('items', function ($join): void {
                $join->on('items.id', '=', 'movements.item_id')
                    ->on('items.tenant_id', '=', 'ledger.tenant_id');
            })
            ->where('ledger.tenant_id', (int) $params['tenant_id'])
            ->where('account_types.statement_type', StatementType::IncomeStatement->value)
            ->where('accounts.normal_balance', NormalBalance::Debit->value)
            ->whereNull('accounts.deleted_at')
            ->select([
                'ledger.id',
                'ledger.entry_date as event_date',
                'ledger.source_number as document',
                'journals.id as journal_id',
                'journals.journal_number as finance_reference',
                'expenses.expense_type_id',
                'expenses.expense_type_code_snapshot',
                'expenses.expense_type_name_snapshot',
                'expenses.payment_method_id_snapshot',
                'expenses.payment_method_code_snapshot',
                'expenses.payment_method_name_snapshot',
                'expenses.reference_number',
                'movements.item_id',
                'items.code as item_code',
                'items.name as item_name',
                'accounts.id as account_id',
                'accounts.code as account_code',
                'accounts.name as account_name',
            ])
            ->selectRaw(
                'CASE WHEN ledger.source_module = ? AND ledger.source_type = ? THEN ? '
                .'WHEN ledger.source_module = ? AND ledger.source_type = ? THEN ? ELSE ? END as source',
                [self::EXPENSE_MODULE, self::EXPENSE_MODULE, self::SOURCE_PAID, self::CONSUMABLE_MODULE, self::CONSUMABLE_TYPE, self::SOURCE_CONSUMABLE, self::SOURCE_OTHER],
            )
            ->selectRaw('CASE WHEN journals.journal_type = ? THEN ? ELSE ? END as event_type', [JournalType::Reversal->value, self::EVENT_REVERSAL, self::EVENT_POSTED])
            ->selectRaw('CASE WHEN journals.journal_type = ? THEN 0 ELSE ledger.debit - ledger.credit END as posted_amount', [JournalType::Reversal->value])
            ->selectRaw('CASE WHEN journals.journal_type = ? THEN ledger.credit - ledger.debit ELSE 0 END as reversed_amount', [JournalType::Reversal->value])
            ->selectRaw('ledger.debit - ledger.credit as net_amount')
            ->selectRaw('CASE WHEN journals.journal_type = ? THEN 0 - movements.quantity ELSE movements.quantity END as quantity', [JournalType::Reversal->value]);

        $organizationUnitId = $this->organizationUnitId($params);
        $organizationUnitId === null
            ? $ledger->whereNull('ledger.organization_unit_id')->whereNull('accounts.organization_unit_id')
            : $ledger->where('ledger.organization_unit_id', $organizationUnitId)->where('accounts.organization_unit_id', $organizationUnitId);

        $events = DB::query()->fromSub($ledger, 'expense_events');

        if (($params['date_from'] ?? null) !== null) {
            $events->whereDate('event_date', '>=', (string) $params['date_from']);
        }
        if (($params['date_to'] ?? null) !== null) {
            $events->whereDate('event_date', '<=', (string) $params['date_to']);
        }
        if (($params['expense_type_id'] ?? null) !== null) {
            $events->where('expense_type_id', (int) $params['expense_type_id']);
        }
        if (($params['payment_method_id'] ?? null) !== null) {
            $events->where('payment_method_id_snapshot', (int) $params['payment_method_id']);
        }
        if (($params['item_id'] ?? null) !== null) {
            $events->where('item_id', (int) $params['item_id']);
        }
        if (($params['source'] ?? null) !== null) {
            $events->where('source', (string) $params['source']);
        }
        if (($params['event_type'] ?? null) !== null) {
            $events->where('event_type', (string) $params['event_type']);
        }

        $search = trim((string) ($params['search'] ?? ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $events->where(function (Builder $query) use ($like): void {
                $query->where('document', 'like', $like)
                    ->orWhere('expense_type_name_snapshot', 'like', $like)
                    ->orWhere('payment_method_name_snapshot', 'like', $like)
                    ->orWhere('reference_number', 'like', $like)
                    ->orWhere('item_name', 'like', $like)
                    ->orWhere('item_code', 'like', $like)
                    ->orWhere('account_name', 'like', $like)
                    ->orWhere('account_code', 'like', $like)
                    ->orWhere('finance_reference', 'like', $like);
            });
        }

        return $events;
    }

    /**
     * @return array<string, mixed>
     */
    private function analytics(Builder $events): array
    {
        $totals = DB::query()->fromSub(clone $events, 'events')->selectRaw(
            'COUNT(*) as event_count, '
            .'COALESCE(SUM(CASE WHEN event_type = ? THEN 1 ELSE 0 END), 0) as posted_count, '
            .'COALESCE(SUM(CASE WHEN event_type = ? THEN 1 ELSE 0 END), 0) as reversal_count, '
            .'COALESCE(SUM(posted_amount), 0) as posted_amount, '
            .'COALESCE(SUM(reversed_amount), 0) as reversed_amount, '
            .'COALESCE(SUM(net_amount), 0) as net_amount',
            [self::EVENT_POSTED, self::EVENT_REVERSAL],
        )->first();

        return [
            'summary' => [
                'event_count' => (int) ($totals->event_count ?? 0),
                'posted_count' => (int) ($totals->posted_count ?? 0),
                'reversal_count' => (int) ($totals->reversal_count ?? 0),
                'posted_amount' => $this->decimal($totals->posted_amount ?? 0),
                'reversed_amount' => $this->decimal($totals->reversed_amount ?? 0),
                'net_amount' => $this->decimal($totals->net_amount ?? 0),
            ],
            'by_expense_type' => $this->breakdown(
                (clone $events)->where('source', self::SOURCE_PAID),
                'expense_type_id',
                'expense_type_code_snapshot',
                'expense_type_name_snapshot',
            ),
            'by_payment_method' => $this->breakdown(
                (clone $events)->where('source', self::SOURCE_PAID),
                'payment_method_id_snapshot',
                'payment_method_code_snapshot',
                'payment_method_name_snapshot',
            ),
            'by_source' => DB::query()->fromSub(clone $events, 'events')
                ->select('source')
                ->selectRaw('COALESCE(SUM(net_amount), 0) as net_amount')
                ->groupBy('source')
                ->get()
                ->map(fn (object $row): array => [
                    'source' => (string) $row->source,
                    'net_amount' => $this->decimal($row->net_amount),
                ])->values()->all(),
            'by_consumable_item' => DB::query()->fromSub((clone $events)->where('source', self::SOURCE_CONSUMABLE), 'events')
                ->select(['item_id', 'item_code', 'item_name'])
                ->selectRaw('COALESCE(SUM(quantity), 0) as quantity')
                ->selectRaw('COALESCE(SUM(net_amount), 0) as net_amount')
                ->groupBy(['item_id', 'item_code', 'item_name'])
                ->orderByDesc('net_amount')
                ->get()
                ->map(fn (object $row): array => [
                    'id' => (int) $row->item_id,
                    'code' => (string) $row->item_code,
                    'name' => (string) $row->item_name,
                    'quantity' => $this->decimal($row->quantity),
                    'net_amount' => $this->decimal($row->net_amount),
                ])->values()->all(),
            'by_other_account' => $this->breakdown(
                (clone $events)->where('source', self::SOURCE_OTHER),
                'account_id',
                'account_code',
                'account_name',
            ),
            'trend' => DB::query()->fromSub(clone $events, 'events')
                ->select('event_date')
                ->selectRaw('COALESCE(SUM(posted_amount), 0) as posted_amount')
                ->selectRaw('COALESCE(SUM(reversed_amount), 0) as reversed_amount')
                ->selectRaw('COALESCE(SUM(net_amount), 0) as net_amount')
                ->groupBy('event_date')
                ->orderBy('event_date')
                ->get()
                ->map(fn (object $row): array => [
                    'date' => substr((string) $row->event_date, 0, 10),
                    'posted_amount' => $this->decimal($row->posted_amount),
                    'reversed_amount' => $this->decimal($row->reversed_amount),
                    'net_amount' => $this->decimal($row->net_amount),
                ])->values()->all(),
        ];
    }

    /**
     * @return list<array<string, int|string>>
     */
    private function breakdown(Builder $events, string $idColumn, string $codeColumn, string $nameColumn): array
    {
        return DB::query()->fromSub($events, 'events')
            ->select([$idColumn, $codeColumn, $nameColumn])
            ->selectRaw('COALESCE(SUM(CASE WHEN event_type = ? THEN 1 ELSE 0 END), 0) as transaction_count', [self::EVENT_POSTED])
            ->selectRaw('COALESCE(SUM(posted_amount), 0) as posted_amount')
            ->selectRaw('COALESCE(SUM(reversed_amount), 0) as reversed_amount')
            ->selectRaw('COALESCE(SUM(net_amount), 0) as net_amount')
            ->groupBy([$idColumn, $codeColumn, $nameColumn])
            ->orderByDesc('net_amount')
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->{$idColumn},
                'code' => (string) $row->{$codeColumn},
                'name' => (string) $row->{$nameColumn},
                'transaction_count' => (int) $row->transaction_count,
                'posted_amount' => $this->decimal($row->posted_amount),
                'reversed_amount' => $this->decimal($row->reversed_amount),
                'net_amount' => $this->decimal($row->net_amount),
            ])->values()->all();
    }

    /**
     * @return array<string, list<array{id:int, name:string}>>
     */
    private function filterOptions(int $tenantId, ?int $organizationUnitId): array
    {
        $query = DB::table('expenses')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [ExpenseStatus::Posted->value, ExpenseStatus::Reversed->value]);
        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        $types = (clone $query)
            ->select(['expense_type_id as id', 'expense_type_name_snapshot as name'])
            ->distinct()
            ->orderBy('expense_type_name_snapshot')
            ->get()
            ->map(fn (object $row): array => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->values()->all();
        $methods = $query
            ->select(['payment_method_id_snapshot as id', 'payment_method_name_snapshot as name'])
            ->distinct()
            ->orderBy('payment_method_name_snapshot')
            ->get()
            ->map(fn (object $row): array => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->values()->all();

        $items = DB::table('inventory_movements as movements')
            ->join('items', function ($join): void {
                $join->on('items.id', '=', 'movements.item_id')
                    ->on('items.tenant_id', '=', 'movements.tenant_id');
            })
            ->where('movements.tenant_id', $tenantId)
            ->where('movements.movement_type', InventoryMovementType::InternalConsumption->value)
            ->when(
                $organizationUnitId === null,
                fn (Builder $builder) => $builder->whereNull('movements.organization_unit_id'),
                fn (Builder $builder) => $builder->where('movements.organization_unit_id', $organizationUnitId),
            )
            ->select(['items.id', 'items.name'])
            ->distinct()
            ->orderBy('items.name')
            ->get()
            ->map(fn (object $row): array => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->values()->all();

        return ['expense_types' => $types, 'payment_methods' => $methods, 'items' => $items];
    }

    /** @param array<string, mixed> $params */
    private function sort(Builder $query, array $params): void
    {
        $columns = [
            'event_date' => 'event_date',
            'event_type' => 'event_type',
            'source' => 'source',
            'document' => 'document',
            'detail' => 'account_name',
            'expense_type' => 'expense_type_name_snapshot',
            'paid_through' => 'payment_method_name_snapshot',
            'net_amount' => 'net_amount',
        ];
        $sort = (string) ($params['sort'] ?? 'event_date');
        $direction = ($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($columns[$sort] ?? 'event_date', $direction)->orderByDesc('id');
    }

    /** @return array<string, mixed> */
    private function row(object $row): array
    {
        return [
            'id' => (string) $row->id,
            'event_date' => substr((string) $row->event_date, 0, 10),
            'event_type' => (string) $row->event_type,
            'source' => match ((string) $row->source) {
                self::SOURCE_PAID => 'Paid expense',
                self::SOURCE_CONSUMABLE => 'Consumable usage',
                default => 'Other P&L expense',
            },
            'document' => (string) ($row->document ?? $row->finance_reference ?? ''),
            'detail' => match ((string) $row->source) {
                self::SOURCE_PAID => (string) ($row->expense_type_name_snapshot ?? $row->account_name),
                self::SOURCE_CONSUMABLE => (string) ($row->item_name ?? $row->account_name),
                default => (string) $row->account_name,
            },
            'paid_through' => (string) ($row->payment_method_name_snapshot ?? ''),
            'reference' => (string) ($row->reference_number ?? ''),
            'finance_reference' => (string) ($row->finance_reference ?? ''),
            'finance_reference_url' => '/finance/journals/'.(int) $row->journal_id,
            'net_amount' => $this->decimal($row->net_amount),
        ];
    }

    /** @param array<string, mixed> $params */
    private function organizationUnitId(array $params): ?int
    {
        $value = $params['organization_unit_id'] ?? null;

        return $value === null || $value === '' ? null : (int) $value;
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? self::ZERO));
    }
}
