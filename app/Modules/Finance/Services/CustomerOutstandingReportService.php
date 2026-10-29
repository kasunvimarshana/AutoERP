<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\ReceivableAgingBucket;
use Modules\Finance\Http\Requests\CustomerOutstandingReportRequest;
use Modules\Invoice\Enums\InvoicePartyType;

final class CustomerOutstandingReportService
{
    private const DUE_DATE_EXPRESSION = 'COALESCE(invoices.due_date, invoices.invoice_date)';

    private const CURRENCY_EXPRESSION = "COALESCE(NULLIF(invoices.currency_code_snapshot, ''), 'N/A')";

    private const CUSTOMER_NAME_EXPRESSION = "COALESCE(NULLIF(customers.display_name, ''), customers.name)";

    public function __construct(
        private readonly CustomerOutstandingAsOfBalances $historicalBalances,
        private readonly CustomerOutstandingLocationScope $locations,
        private readonly DecimalMath $math,
    ) {}

    /** @return array<int, array{id: int, name: string}> */
    public function locations(CustomerOutstandingReportRequest $request): array
    {
        return $this->locations->options($request);
    }

    /** @return array<string, mixed> */
    public function page(CustomerOutstandingReportRequest $request): array
    {
        $asOfDate = $this->asOfDate($request);
        $this->historicalBalances->assertHistoricalCoverage($request, $asOfDate, $this->locations);
        $query = $this->baseQuery($request, $asOfDate);
        $groups = $this->groupedQuery(clone $query, $asOfDate);
        $paginator = $groups
            ->orderBy('locations.name')
            ->orderBy('customer_name')
            ->orderBy('invoices.currency_code_snapshot')
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
    public function invoices(CustomerOutstandingReportRequest $request, int $customerId): array
    {
        $asOfDate = $this->asOfDate($request);
        $this->historicalBalances->assertHistoricalCoverage($request, $asOfDate, $this->locations);
        $currencyCode = (string) $request->input('currency_code');
        $query = $this->baseQuery($request, $asOfDate)
            ->where('customers.id', $customerId)
            ->whereRaw(self::CURRENCY_EXPRESSION.' = ?', [$currencyCode])
            ->select([
                'invoices.id as invoice_id',
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.due_date',
                'invoices.grand_total',
                'balances.paid_amount',
                'balances.credit_allocated_amount',
                'balances.remaining_amount',
                'invoices.currency_code_snapshot',
            ])
            ->selectSub(
                DB::table('invoice_sources')
                    ->select('source_document_number')
                    ->whereColumn('invoice_sources.invoice_id', 'invoices.id')
                    ->whereColumn('invoice_sources.tenant_id', 'invoices.tenant_id')
                    ->where('invoice_sources.source_type', 'vehicle_service_job')
                    ->limit(1),
                'service_job_number',
            )
            ->orderByRaw(self::DUE_DATE_EXPRESSION)
            ->orderBy('invoices.invoice_number');

        $paginator = $query->paginate($request->perPage());
        $rows = $paginator->getCollection()->map(fn (object $row): array => [
            'invoice' => [
                'id' => (int) $row->invoice_id,
                'number' => (string) $row->invoice_number,
                'date' => CarbonImmutable::parse($row->invoice_date)->toDateString(),
                'due_date' => CarbonImmutable::parse($row->due_date ?? $row->invoice_date)->toDateString(),
                'service_job_number' => $row->service_job_number === null ? null : (string) $row->service_job_number,
            ],
            'currency' => ['code' => $row->currency_code_snapshot ?: 'N/A'],
            'invoice_total' => (string) $row->grand_total,
            'paid_amount' => (string) $row->paid_amount,
            'credit_allocated_amount' => (string) $row->credit_allocated_amount,
            'outstanding_amount' => (string) $row->remaining_amount,
            'invoice_age_days' => $this->invoiceAgeDays($row->invoice_date, $asOfDate),
            'days_overdue' => $this->daysOverdue($row->due_date ?? $row->invoice_date, $asOfDate),
        ])->values();

        return [
            'as_of_date' => $asOfDate,
            'customer' => $paginator->total() > 0 ? $this->customerIdentity($customerId, $request) : null,
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

    /** @return iterable<array<string, string>> */
    public function exportRows(CustomerOutstandingReportRequest $request): iterable
    {
        $asOfDate = $this->asOfDate($request);
        $this->historicalBalances->assertHistoricalCoverage($request, $asOfDate, $this->locations);
        $this->locations->assertAccessible($request);

        return $this->streamExportRows($request, $asOfDate);
    }

    /** @return iterable<array<string, string>> */
    private function streamExportRows(CustomerOutstandingReportRequest $request, string $asOfDate): iterable
    {
        $query = $this->baseQuery($request, $asOfDate);
        $summary = $this->summary(clone $query, $asOfDate);
        $invoices = $query
            ->select([
                'locations.id as location_id',
                'locations.name as location_name',
                'customers.id as customer_id',
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.due_date',
                'invoices.currency_code_snapshot',
                'balances.remaining_amount',
            ])
            ->selectRaw(self::CUSTOMER_NAME_EXPRESSION.' as customer_name')
            ->orderBy('locations.name')
            ->orderBy('customers.name')
            ->orderBy('invoices.currency_code_snapshot')
            ->orderBy('invoices.invoice_date')
            ->orderBy('invoices.invoice_number')
            ->lazy(500);

        $currentLocation = null;
        $currentCustomer = null;
        $customerName = '';
        $customerCurrency = '';
        $customerTotal = '0.000000';
        foreach ($invoices as $invoice) {
            $locationKey = (string) ($invoice->location_id ?? 'unassigned');
            $currency = (string) ($invoice->currency_code_snapshot ?: 'N/A');
            $customerKey = $locationKey.'|'.$invoice->customer_id.'|'.$currency;
            if ($currentCustomer !== null && $customerKey !== $currentCustomer) {
                yield $this->exportTotal('customer_total', '', "{$customerName} total", $customerCurrency, $customerTotal);
                $customerTotal = '0.000000';
            }
            if ($currentLocation !== null && $locationKey !== $currentLocation) {
                foreach ($this->exportLocationTotals($summary['totals_by_location'], $currentLocation) as $total) {
                    yield $total;
                }
            }

            $currentLocation = $locationKey;
            $currentCustomer = $customerKey;
            $customerName = (string) $invoice->customer_name;
            $customerCurrency = $currency;
            $customerTotal = $this->math->add($customerTotal, (string) $invoice->remaining_amount);
            yield [
                'row_type' => 'invoice',
                'location' => (string) ($invoice->location_name ?? 'Unassigned'),
                'customer' => $customerName,
                'invoice' => (string) $invoice->invoice_number,
                'invoice_date' => (string) $invoice->invoice_date,
                'invoice_age_days' => (string) $this->invoiceAgeDays($invoice->invoice_date, $asOfDate),
                'due_days' => (string) $this->daysOverdue($invoice->due_date ?? $invoice->invoice_date, $asOfDate),
                'currency' => $currency,
                'balance' => (string) $invoice->remaining_amount,
            ];
        }
        if ($currentCustomer !== null) {
            yield $this->exportTotal('customer_total', '', "{$customerName} total", $customerCurrency, $customerTotal);
        }
        if ($currentLocation !== null) {
            foreach ($this->exportLocationTotals($summary['totals_by_location'], $currentLocation) as $total) {
                yield $total;
            }
        }
        foreach ($summary['totals_by_currency'] as $total) {
            yield $this->exportTotal('grand_total', 'Grand total', '', $total['currency']['code'], $total['outstanding_total']);
        }
    }

    /** @param iterable<array<string, mixed>> $totals @return iterable<array<string, string>> */
    private function exportLocationTotals(iterable $totals, string $locationKey): iterable
    {
        foreach ($totals as $total) {
            if ((string) ($total['location']['id'] ?? 'unassigned') !== $locationKey) {
                continue;
            }

            yield $this->exportTotal(
                'location_total',
                $total['location']['name'].' total',
                '',
                $total['currency']['code'],
                $total['outstanding_total'],
            );
        }
    }

    /** @return array<string, string> */
    private function exportTotal(string $type, string $location, string $customer, string $currency, string $balance): array
    {
        return [
            'row_type' => $type,
            'location' => $location,
            'customer' => $customer,
            'invoice' => '',
            'invoice_date' => '',
            'invoice_age_days' => '',
            'due_days' => '',
            'currency' => $currency,
            'balance' => $balance,
        ];
    }

    private function baseQuery(CustomerOutstandingReportRequest $request, string $asOfDate): Builder
    {
        $query = DB::query()
            ->fromSub($this->historicalBalances->balances($request->tenantId(), $asOfDate), 'balances')
            ->join('invoices', static function ($join): void {
                $join->on('invoices.id', '=', 'balances.invoice_id')
                    ->on('invoices.tenant_id', '=', 'balances.tenant_id');
            })
            ->join('customers', static function ($join): void {
                $join->on('customers.id', '=', 'invoices.party_id')
                    ->on('customers.tenant_id', '=', 'invoices.tenant_id');
            })
            ->leftJoin('organization_units as locations', static function ($join): void {
                $join->on('locations.id', '=', 'balances.organization_unit_id')
                    ->on('locations.tenant_id', '=', 'balances.tenant_id');
            })
            ->where('balances.tenant_id', $request->tenantId())
            ->where('invoices.party_type', InvoicePartyType::Customer->value)
            ->where('invoices.direction', 'outbound')
            ->leftJoinSub($this->historicalBalances->invoiceReversalDates($request->tenantId()), 'invoice_reversals', 'invoice_reversals.invoice_id', '=', 'invoices.id')
            ->whereNotIn('invoices.status', ['draft', 'approved'])
            ->where(function (Builder $query) use ($asOfDate): void {
                $query->whereNotIn('invoices.status', ['cancelled', 'void', 'reversed'])
                    ->orWhere(function (Builder $cancelled) use ($asOfDate): void {
                        $cancelled->whereIn('invoices.status', ['cancelled', 'void'])
                            ->whereDate('invoices.cancelled_at', '>', $asOfDate);
                    })
                    ->orWhere(function (Builder $reversed) use ($asOfDate): void {
                        $reversed->where('invoices.status', 'reversed')
                            ->where('invoice_reversals.reversal_date', '>', $asOfDate);
                    });
            })
            ->where('balances.remaining_amount', '>', 0)
            ->where('invoices.invoice_date', '<=', $asOfDate);

        $this->locations->apply($query, $request);

        if ($request->filled('customer_id')) {
            $query->where('customers.id', (int) $request->input('customer_id'));
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
            'locations.id as location_id',
            'locations.name as location_name',
            'customers.id as customer_id',
            'customers.name as registered_name',
            'customers.display_name',
            'customers.code as customer_code',
            'customers.customer_number',
        ])->selectRaw(self::CUSTOMER_NAME_EXPRESSION.' as customer_name')
            ->addSelect('invoices.currency_code_snapshot as currency_code')
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
                'locations.id',
                'locations.name',
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
        $totalsByLocation = (clone $query)
            ->select([
                'locations.id as location_id',
                'locations.name as location_name',
                'invoices.currency_code_snapshot as currency_code',
            ])
            ->selectRaw('SUM(balances.remaining_amount) as outstanding_total')
            ->groupBy('locations.id', 'locations.name', 'invoices.currency_code_snapshot')
            ->orderBy('locations.name')
            ->get()
            ->map(fn (object $row): array => [
                'location' => ['id' => $row->location_id === null ? null : (int) $row->location_id, 'name' => (string) ($row->location_name ?? 'Unassigned')],
                'currency' => ['code' => $row->currency_code ?: 'N/A'],
                'outstanding_total' => (string) $row->outstanding_total,
            ])
            ->values();

        return [
            'customer_count' => $customerCount,
            'overdue_invoice_count' => $overdueInvoiceCount,
            'totals_by_currency' => $totalsByCurrency,
            'totals_by_location' => $totalsByLocation,
        ];
    }

    /** @return array<string, mixed> */
    private function customerRow(object $row): array
    {
        return [
            'location' => [
                'id' => $row->location_id === null ? null : (int) $row->location_id,
                'name' => (string) ($row->location_name ?? 'Unassigned'),
            ],
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
    private function customerIdentity(int $customerId, CustomerOutstandingReportRequest $request): ?array
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

    private function asOfDate(CustomerOutstandingReportRequest $request): string
    {
        return CarbonImmutable::parse((string) $request->input('as_of_date', $request->reportToday()))->toDateString();
    }

    private function invoiceAgeDays(string $invoiceDate, string $asOfDate): int
    {
        return (int) CarbonImmutable::parse($invoiceDate)->diffInDays(CarbonImmutable::parse($asOfDate));
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
