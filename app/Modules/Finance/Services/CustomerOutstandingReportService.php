<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\Customer;
use Modules\Finance\Enums\ReceivableAgingBucket;
use Modules\Finance\Http\Requests\ListFinanceRequest;

final class CustomerOutstandingReportService
{
    private const DUE_DATE_EXPRESSION = 'COALESCE(invoices.due_date, invoices.invoice_date)';

    private const CURRENCY_EXPRESSION = "COALESCE(NULLIF(invoices.currency_code_snapshot, ''), 'N/A')";

    private const CUSTOMER_NAME_EXPRESSION = "COALESCE(NULLIF(customers.display_name, ''), customers.name)";

    /** @return array<string, mixed> */
    public function page(ListFinanceRequest $request): array
    {
        $asOfDate = $this->asOfDate($request);
        $query = $this->baseQuery($request, $asOfDate);
        $groups = $this->groupedQuery(clone $query, $asOfDate);
        $paginator = $groups
            ->orderByDesc('outstanding_total')
            ->orderBy('customer_name')
            ->paginate($request->perPage());

        return [
            'as_of_date' => $asOfDate,
            'summary' => $this->summary($query, $asOfDate),
            'data' => $paginator->getCollection()->map(fn (object $row): array => $this->customerRow($row))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function invoices(ListFinanceRequest $request, int $customerId): array
    {
        $asOfDate = $this->asOfDate($request);
        $currencyCode = (string) $request->input('currency_code');
        $query = $this->baseQuery($request, $asOfDate)
            ->where('customers.id', $customerId)
            ->whereRaw(self::CURRENCY_EXPRESSION.' = ?', [$currencyCode])
            ->select([
                'invoices.id as invoice_id',
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.due_date',
                'invoices.status as invoice_status',
                'invoices.grand_total',
                'balances.paid_amount',
                'balances.credit_allocated_amount',
                'balances.remaining_amount',
                'invoices.currency_code_snapshot',
            ])
            ->orderByRaw(self::DUE_DATE_EXPRESSION)
            ->orderBy('invoices.invoice_number');

        $paginator = $query->paginate($request->perPage());
        $rows = $paginator->getCollection()->map(fn (object $row): array => [
            'invoice' => [
                'id' => (int) $row->invoice_id,
                'number' => (string) $row->invoice_number,
                'date' => CarbonImmutable::parse($row->invoice_date)->toDateString(),
                'due_date' => CarbonImmutable::parse($row->due_date ?? $row->invoice_date)->toDateString(),
                'status' => (string) $row->invoice_status,
            ],
            'currency' => ['code' => $row->currency_code_snapshot ?: 'N/A'],
            'invoice_total' => (string) $row->grand_total,
            'paid_amount' => (string) $row->paid_amount,
            'credit_allocated_amount' => (string) $row->credit_allocated_amount,
            'outstanding_amount' => (string) $row->remaining_amount,
            'days_overdue' => $this->daysOverdue($row->due_date ?? $row->invoice_date, $asOfDate),
        ])->values();

        return [
            'as_of_date' => $asOfDate,
            'customer' => $this->customerIdentity($customerId, $request),
            'currency' => ['code' => $currencyCode],
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /** @return iterable<object> */
    public function exportRows(ListFinanceRequest $request): iterable
    {
        $asOfDate = $this->asOfDate($request);

        return $this->groupedQuery($this->baseQuery($request, $asOfDate), $asOfDate)
            ->orderBy('customer_name')
            ->orderBy('currency_code')
            ->lazy(500);
    }

    private function baseQuery(ListFinanceRequest $request, string $asOfDate): Builder
    {
        $query = DB::table('invoice_balances as balances')
            ->join('invoices', static function ($join): void {
                $join->on('invoices.id', '=', 'balances.invoice_id')
                    ->on('invoices.tenant_id', '=', 'balances.tenant_id');
            })
            ->join('customers', static function ($join): void {
                $join->on('customers.id', '=', 'invoices.party_id')
                    ->on('customers.tenant_id', '=', 'invoices.tenant_id');
            })
            ->where('balances.tenant_id', $request->tenantId())
            ->where('invoices.party_type', Customer::class)
            ->where('invoices.direction', 'outbound')
            ->whereNotIn('invoices.status', ['cancelled', 'void'])
            ->where('balances.remaining_amount', '>', 0)
            ->where('invoices.invoice_date', '<=', $asOfDate);

        if ($request->organizationUnitId() === null) {
            $query->whereNull('balances.organization_unit_id');
        } else {
            $query->where('balances.organization_unit_id', $request->organizationUnitId());
        }

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(static fn (Builder $searchQuery): Builder => $searchQuery
                ->where('customers.name', 'like', $search)
                ->orWhere('customers.display_name', 'like', $search)
                ->orWhere('customers.code', 'like', $search)
                ->orWhere('customers.customer_number', 'like', $search));
        }

        if ($request->filled('currency_code')) {
            $query->whereRaw(self::CURRENCY_EXPRESSION.' = ?', [strtoupper((string) $request->input('currency_code'))]);
        }

        if ($request->filled('aging_bucket')) {
            $this->applyAgingBucket(
                $query,
                ReceivableAgingBucket::from((string) $request->input('aging_bucket')),
                $asOfDate,
            );
        }

        return $query;
    }

    private function applyAgingBucket(Builder $query, ReceivableAgingBucket $bucket, string $asOfDate): void
    {
        $dueDate = self::DUE_DATE_EXPRESSION;
        $asOf = CarbonImmutable::parse($asOfDate);

        match ($bucket) {
            ReceivableAgingBucket::Current => $query->whereRaw($dueDate.' >= ?', [$asOfDate]),
            ReceivableAgingBucket::Days1To30 => $query->whereRaw($dueDate.' < ? AND '.$dueDate.' >= ?', [
                $asOfDate,
                $asOf->subDays(30)->toDateString(),
            ]),
            ReceivableAgingBucket::Days31To60 => $query->whereRaw($dueDate.' < ? AND '.$dueDate.' >= ?', [
                $asOf->subDays(30)->toDateString(),
                $asOf->subDays(60)->toDateString(),
            ]),
            ReceivableAgingBucket::Days61To90 => $query->whereRaw($dueDate.' < ? AND '.$dueDate.' >= ?', [
                $asOf->subDays(60)->toDateString(),
                $asOf->subDays(90)->toDateString(),
            ]),
            ReceivableAgingBucket::Over90 => $query->whereRaw($dueDate.' < ?', [$asOf->subDays(90)->toDateString()]),
        };
    }

    private function groupedQuery(Builder $query, string $asOfDate): Builder
    {
        $dueDate = self::DUE_DATE_EXPRESSION;

        return $query->select([
            'customers.id as customer_id',
            'customers.name as registered_name',
            'customers.display_name',
            'customers.code as customer_code',
            'customers.customer_number',
        ])->selectRaw(self::CUSTOMER_NAME_EXPRESSION.' as customer_name')
            ->select('invoices.currency_code_snapshot as currency_code')
            ->selectRaw('COUNT(invoices.id) as invoice_count')
            ->selectRaw('SUM(balances.remaining_amount) as outstanding_total')
            ->selectRaw('SUM(CASE WHEN '.$dueDate.' >= ? THEN balances.remaining_amount ELSE 0 END) as current_amount', [$asOfDate])
            ->selectRaw('SUM(CASE WHEN '.$dueDate.' < ? AND '.$dueDate.' >= ? THEN balances.remaining_amount ELSE 0 END) as days_1_30_amount', [
                $asOfDate,
                CarbonImmutable::parse($asOfDate)->subDays(30)->toDateString(),
            ])
            ->selectRaw('SUM(CASE WHEN '.$dueDate.' < ? AND '.$dueDate.' >= ? THEN balances.remaining_amount ELSE 0 END) as days_31_60_amount', [
                CarbonImmutable::parse($asOfDate)->subDays(30)->toDateString(),
                CarbonImmutable::parse($asOfDate)->subDays(60)->toDateString(),
            ])
            ->selectRaw('SUM(CASE WHEN '.$dueDate.' < ? AND '.$dueDate.' >= ? THEN balances.remaining_amount ELSE 0 END) as days_61_90_amount', [
                CarbonImmutable::parse($asOfDate)->subDays(60)->toDateString(),
                CarbonImmutable::parse($asOfDate)->subDays(90)->toDateString(),
            ])
            ->selectRaw('SUM(CASE WHEN '.$dueDate.' < ? THEN balances.remaining_amount ELSE 0 END) as over_90_amount', [
                CarbonImmutable::parse($asOfDate)->subDays(90)->toDateString(),
            ])
            ->groupBy([
                'customers.id',
                'customers.name',
                'customers.display_name',
                'customers.code',
                'customers.customer_number',
                'invoices.currency_code_snapshot',
            ]);
    }

    /** @return array<string, mixed> */
    private function summary(Builder $query, string $asOfDate): array
    {
        $customerCount = (clone $query)->distinct()->count('customers.id');
        $overdueInvoiceCount = (clone $query)
            ->whereRaw(self::DUE_DATE_EXPRESSION.' < ?', [$asOfDate])
            ->count('invoices.id');
        $totalsByCurrency = (clone $query)
            ->select('invoices.currency_code_snapshot as currency_code')
            ->selectRaw('SUM(balances.remaining_amount) as outstanding_total')
            ->selectRaw(
                'SUM(CASE WHEN '.self::DUE_DATE_EXPRESSION.' < ? THEN balances.remaining_amount ELSE 0 END) as overdue_total',
                [$asOfDate],
            )
            ->groupBy('invoices.currency_code_snapshot')
            ->orderBy('currency_code')
            ->get()
            ->map(fn (object $row): array => [
                'currency' => ['code' => $row->currency_code ?: 'N/A'],
                'outstanding_total' => (string) $row->outstanding_total,
                'overdue_total' => (string) $row->overdue_total,
            ])
            ->values();

        return [
            'customer_count' => $customerCount,
            'overdue_invoice_count' => $overdueInvoiceCount,
            'totals_by_currency' => $totalsByCurrency,
        ];
    }

    /** @return array<string, mixed> */
    private function customerRow(object $row): array
    {
        return [
            'customer' => [
                'id' => (int) $row->customer_id,
                'name' => (string) $row->customer_name,
                'code' => (string) $row->customer_code,
                'customer_number' => (string) $row->customer_number,
            ],
            'currency' => ['code' => (string) ($row->currency_code ?: 'N/A')],
            'invoice_count' => (int) $row->invoice_count,
            'current' => (string) $row->current_amount,
            'days_1_30' => (string) $row->days_1_30_amount,
            'days_31_60' => (string) $row->days_31_60_amount,
            'days_61_90' => (string) $row->days_61_90_amount,
            'over_90' => (string) $row->over_90_amount,
            'outstanding_total' => (string) $row->outstanding_total,
        ];
    }

    /** @return array<string, mixed>|null */
    private function customerIdentity(int $customerId, ListFinanceRequest $request): ?array
    {
        $customer = DB::table('customers')
            ->where('tenant_id', $request->tenantId())
            ->where('id', $customerId)
            ->first();

        if ($customer === null) {
            return null;
        }

        return [
            'id' => (int) $customer->id,
            'name' => $customer->display_name ?: $customer->name,
            'code' => (string) $customer->code,
            'customer_number' => (string) $customer->customer_number,
        ];
    }

    private function asOfDate(ListFinanceRequest $request): string
    {
        return CarbonImmutable::parse((string) $request->input('as_of_date', now()->toDateString()))->toDateString();
    }

    private function daysOverdue(string $dueDate, string $asOfDate): int
    {
        $due = CarbonImmutable::parse($dueDate);
        $asOf = CarbonImmutable::parse($asOfDate);
        if ($due->greaterThanOrEqualTo($asOf)) {
            return 0;
        }

        return (int) $due->diffInDays($asOf);
    }
}
