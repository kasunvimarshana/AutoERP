<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Expense\Enums\ExpenseStatus;
use Modules\Expense\Models\Expense;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;

final class ExpenseReportService
{
    public const REPORT_KEY = 'expenses';

    private const EVENT_POSTED = 'posted';

    private const EVENT_REVERSAL = 'reversal';

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
            'trend' => $overview['trend'],
            'filter_options' => $this->filterOptions((int) $params['tenant_id'], $organizationUnitId),
            'basis' => 'Posting events use the expense date. Reversal events use the reversal date, so net expense reconciles with the General Ledger for the selected period.',
        ];
    }

    /**
     * @return array{summary: array<string, int|string>, by_expense_type: list<array<string, int|string>>, by_payment_method: list<array<string, int|string>>, trend: list<array<string, int|string>>}
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
            title: 'Expense Report',
            group: 'Finance',
            model: Expense::class,
            columns: [
                new ReportColumn('event_date', 'Date', sortBy: 'event_date', format: 'date'),
                new ReportColumn('event_type', 'Activity', sortBy: 'event_type', format: 'enum'),
                new ReportColumn('expense_number', 'Expense', sortBy: 'expense_number'),
                new ReportColumn('expense_type', 'Type', sortBy: 'expense_type'),
                new ReportColumn('paid_through', 'Paid through', sortBy: 'paid_through'),
                new ReportColumn('reference', 'Reference'),
                new ReportColumn('finance_reference', 'Finance reference'),
                new ReportColumn('net_amount', 'Net amount', sortBy: 'net_amount', format: 'money', summarize: true),
            ],
            dateColumn: 'event_date',
            defaultSort: 'event_date',
            defaultDirection: 'desc',
            description: 'Posted operating expenses and dated reversals reconciled to the General Ledger.',
            orientation: 'landscape',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function events(array $params): Builder
    {
        $postings = $this->baseExpenses($params)
            ->select([
                'expenses.id',
                'expenses.expense_number',
                'expenses.expense_date',
                'expenses.reversal_date',
                'expenses.expense_type_id',
                'expenses.expense_type_code_snapshot',
                'expenses.expense_type_name_snapshot',
                'expenses.payment_method_id_snapshot',
                'expenses.payment_method_code_snapshot',
                'expenses.payment_method_name_snapshot',
                'expenses.payment_method_type_snapshot',
                'expenses.reference_number',
                'expenses.status',
            ])
            ->selectRaw('? as event_type', [self::EVENT_POSTED])
            ->selectRaw('expenses.expense_date as event_date')
            ->selectRaw('expenses.amount as posted_amount')
            ->selectRaw('0 as reversed_amount')
            ->selectRaw('expenses.amount as net_amount')
            ->selectRaw('expenses.finance_posting_reference as finance_reference');

        $reversals = $this->baseExpenses($params)
            ->where('expenses.status', ExpenseStatus::Reversed->value)
            ->whereNotNull('expenses.reversal_date')
            ->select([
                'expenses.id',
                'expenses.expense_number',
                'expenses.expense_date',
                'expenses.reversal_date',
                'expenses.expense_type_id',
                'expenses.expense_type_code_snapshot',
                'expenses.expense_type_name_snapshot',
                'expenses.payment_method_id_snapshot',
                'expenses.payment_method_code_snapshot',
                'expenses.payment_method_name_snapshot',
                'expenses.payment_method_type_snapshot',
                'expenses.reference_number',
                'expenses.status',
            ])
            ->selectRaw('? as event_type', [self::EVENT_REVERSAL])
            ->selectRaw('expenses.reversal_date as event_date')
            ->selectRaw('0 as posted_amount')
            ->selectRaw('expenses.amount as reversed_amount')
            ->selectRaw('0 - expenses.amount as net_amount')
            ->selectRaw('expenses.finance_reversal_reference as finance_reference');

        $events = DB::query()->fromSub($postings->unionAll($reversals), 'expense_events');

        if (($params['date_from'] ?? null) !== null) {
            $events->where('event_date', '>=', (string) $params['date_from']);
        }
        if (($params['date_to'] ?? null) !== null) {
            $events->where('event_date', '<=', (string) $params['date_to']);
        }
        if (($params['expense_type_id'] ?? null) !== null) {
            $events->where('expense_type_id', (int) $params['expense_type_id']);
        }
        if (($params['payment_method_id'] ?? null) !== null) {
            $events->where('payment_method_id_snapshot', (int) $params['payment_method_id']);
        }
        if (($params['event_type'] ?? null) !== null) {
            $events->where('event_type', (string) $params['event_type']);
        }

        $search = trim((string) ($params['search'] ?? ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $events->where(function (Builder $query) use ($like): void {
                $query->where('expense_number', 'like', $like)
                    ->orWhere('expense_type_name_snapshot', 'like', $like)
                    ->orWhere('payment_method_name_snapshot', 'like', $like)
                    ->orWhere('reference_number', 'like', $like);
            });
        }

        return $events;
    }

    /** @param array<string, mixed> $params */
    private function baseExpenses(array $params): Builder
    {
        $query = DB::table('expenses')
            ->where('expenses.tenant_id', (int) $params['tenant_id'])
            ->whereIn('expenses.status', [ExpenseStatus::Posted->value, ExpenseStatus::Reversed->value]);

        $organizationUnitId = $this->organizationUnitId($params);
        $organizationUnitId === null
            ? $query->whereNull('expenses.organization_unit_id')
            : $query->where('expenses.organization_unit_id', $organizationUnitId);

        return $query;
    }

    /**
     * @return array{summary: array<string, int|string>, by_expense_type: list<array<string, int|string>>, by_payment_method: list<array<string, int|string>>, trend: list<array<string, int|string>>}
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
                clone $events,
                'expense_type_id',
                'expense_type_code_snapshot',
                'expense_type_name_snapshot',
            ),
            'by_payment_method' => $this->breakdown(
                clone $events,
                'payment_method_id_snapshot',
                'payment_method_code_snapshot',
                'payment_method_name_snapshot',
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
                    'date' => (string) $row->event_date,
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
     * @return array{expense_types: list<array{id:int, name:string}>, payment_methods: list<array{id:int, name:string}>}
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

        return ['expense_types' => $types, 'payment_methods' => $methods];
    }

    /** @param array<string, mixed> $params */
    private function sort(Builder $query, array $params): void
    {
        $columns = [
            'event_date' => 'event_date',
            'event_type' => 'event_type',
            'expense_number' => 'expense_number',
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
            'id' => (string) $row->event_type.'-'.(int) $row->id,
            'event_date' => (string) $row->event_date,
            'event_type' => (string) $row->event_type,
            'expense_number' => (string) $row->expense_number,
            'expense_type' => (string) $row->expense_type_name_snapshot,
            'paid_through' => (string) $row->payment_method_name_snapshot,
            'reference' => (string) ($row->reference_number ?? ''),
            'finance_reference' => (string) ($row->finance_reference ?? ''),
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
