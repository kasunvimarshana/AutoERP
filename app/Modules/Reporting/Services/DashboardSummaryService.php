<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;

final class DashboardSummaryService
{
    private const FINAL_INVOICE_STATUSES = [
        InvoiceStatus::Posted->value,
        InvoiceStatus::PartiallyPaid->value,
        InvoiceStatus::Paid->value,
    ];

    private const ACTIVE_SERVICE_JOB_STATUSES = [
        VehicleServiceJobStatus::Draft->value,
        VehicleServiceJobStatus::Inspected->value,
        VehicleServiceJobStatus::InProgress->value,
    ];

    private const EXPIRING_BATCH_DAYS = 30;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly ReportBrandingResolver $branding,
    ) {}

    /** @return array<string, mixed> */
    public function run(int $tenantId, ?int $organizationUnitId, string $dateFrom, string $dateTo): array
    {
        $today = CarbonImmutable::today();
        $inventory = $this->inventoryHealth($tenantId, $organizationUnitId, $today);
        $receivables = $this->aging($tenantId, $organizationUnitId, InvoiceDirection::Outbound, $today);
        $payables = $this->aging($tenantId, $organizationUnitId, InvoiceDirection::Inbound, $today);
        $overdueJobs = $this->overdueServiceJobs($tenantId, $organizationUnitId, $today);
        $failedPayments = $this->failedPayments($tenantId, $organizationUnitId);
        $branding = $this->branding->resolve($tenantId, $organizationUnitId);

        return [
            'period' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'currency_code' => (string) ($branding['currency_code'] ?? ''),
            'kpis' => [
                'today_revenue' => $this->revenueTotal($tenantId, $organizationUnitId, $today->toDateString(), $today->toDateString()),
                'period_revenue' => $this->revenueTotal($tenantId, $organizationUnitId, $dateFrom, $dateTo),
                'receivables' => $receivables['total'],
                'payables' => $payables['total'],
                'active_service_jobs' => $this->activeServiceJobs($tenantId, $organizationUnitId),
                'inventory_value' => $inventory['inventory_value'],
            ],
            'revenue_trend' => $this->revenueTrend($tenantId, $organizationUnitId, $dateFrom, $dateTo),
            'service_jobs' => $this->serviceJobStatus($tenantId, $organizationUnitId, $dateFrom, $dateTo),
            'cash_flow' => $this->cashFlow($tenantId, $organizationUnitId, $dateFrom, $dateTo),
            'inventory_health' => $inventory,
            'aging' => [
                'receivables' => $receivables['buckets'],
                'payables' => $payables['buckets'],
            ],
            'actions' => $this->actions(
                $receivables['overdue_count'],
                $payables['overdue_count'],
                $overdueJobs,
                $inventory['low_stock_items'],
                $inventory['expiring_batches'],
                $failedPayments,
            ),
        ];
    }

    private function revenueTotal(int $tenantId, ?int $organizationUnitId, string $dateFrom, string $dateTo): string
    {
        $query = $this->invoiceQuery($tenantId, $organizationUnitId, InvoiceDirection::Outbound)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereNotIn('invoice_type', [InvoiceType::Credit->value, InvoiceType::Debit->value]);

        return $this->decimal($query->sum('grand_total'));
    }

    /** @return list<array{key:string,label:string,value:string}> */
    private function revenueTrend(int $tenantId, ?int $organizationUnitId, string $dateFrom, string $dateTo): array
    {
        $buckets = $this->monthlyBuckets($dateFrom, $dateTo, ['value' => '0.000000']);
        $rows = $this->invoiceQuery($tenantId, $organizationUnitId, InvoiceDirection::Outbound)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereNotIn('invoice_type', [InvoiceType::Credit->value, InvoiceType::Debit->value])
            ->select('invoice_date')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as amount')
            ->groupBy('invoice_date')
            ->orderBy('invoice_date')
            ->get();

        foreach ($rows as $row) {
            $key = CarbonImmutable::parse((string) $row->invoice_date)->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['value'] = $this->math->add($buckets[$key]['value'], (string) $row->amount);
            }
        }

        return array_values($buckets);
    }

    /** @return list<array{status:string,label:string,count:int,url:string}> */
    private function serviceJobStatus(int $tenantId, ?int $organizationUnitId, string $dateFrom, string $dateTo): array
    {
        $query = DB::table('vehicle_service_jobs')
            ->where('tenant_id', $tenantId)
            ->whereBetween('job_date', [$dateFrom, $dateTo])
            ->whereNull('deleted_at');
        $this->organizationScope($query, 'organization_unit_id', $organizationUnitId);
        $counts = $query->select('status')->selectRaw('COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return array_map(static fn (VehicleServiceJobStatus $status): array => [
            'status' => $status->value,
            'label' => ucwords(str_replace('_', ' ', $status->value)),
            'count' => (int) ($counts[$status->value] ?? 0),
            'url' => '/vehicle-service/jobs?status='.$status->value,
        ], VehicleServiceJobStatus::cases());
    }

    /** @return list<array{key:string,label:string,inbound:string,outbound:string,net:string}> */
    private function cashFlow(int $tenantId, ?int $organizationUnitId, string $dateFrom, string $dateTo): array
    {
        $buckets = $this->monthlyBuckets($dateFrom, $dateTo, [
            'inbound' => '0.000000',
            'outbound' => '0.000000',
            'net' => '0.000000',
        ]);
        $query = DB::table('payment_lines as lines')
            ->join('payments', 'payments.id', '=', 'lines.payment_id')
            ->where('payments.tenant_id', $tenantId)
            ->where('lines.tenant_id', $tenantId)
            ->where('payments.document_status', PaymentDocumentStatus::Approved->value)
            ->where('payments.posting_status', PaymentPostingStatus::Posted->value)
            ->whereBetween('payments.payment_date', [$dateFrom, $dateTo])
            ->whereNull('payments.deleted_at');
        $this->organizationScope($query, 'payments.organization_unit_id', $organizationUnitId);
        $this->organizationScope($query, 'lines.organization_unit_id', $organizationUnitId);
        $rows = $query
            ->select(['payments.payment_date', 'payments.direction'])
            ->selectRaw('COALESCE(SUM(lines.amount), 0) as amount')
            ->groupBy(['payments.payment_date', 'payments.direction'])
            ->orderBy('payments.payment_date')
            ->get();

        foreach ($rows as $row) {
            $key = CarbonImmutable::parse((string) $row->payment_date)->format('Y-m');
            $direction = (string) $row->direction;
            if (! isset($buckets[$key]) || ! in_array($direction, [PaymentDirection::Inbound->value, PaymentDirection::Outbound->value], true)) {
                continue;
            }
            $buckets[$key][$direction] = $this->math->add($buckets[$key][$direction], (string) $row->amount);
        }
        foreach ($buckets as &$bucket) {
            $bucket['net'] = $this->math->sub($bucket['inbound'], $bucket['outbound']);
        }
        unset($bucket);

        return array_values($buckets);
    }

    /** @return array{inventory_value:string,in_stock_items:int,low_stock_items:int,out_of_stock_items:int,expiring_batches:int} */
    private function inventoryHealth(int $tenantId, ?int $organizationUnitId, CarbonImmutable $today): array
    {
        $balances = DB::table('inventory_stock_balances')
            ->where('tenant_id', $tenantId);
        $this->organizationScope($balances, 'organization_unit_id', $organizationUnitId);
        $inventoryValue = $this->decimal((clone $balances)->sum('total_value'));

        $items = DB::table('items')
            ->leftJoin('inventory_stock_balances as balances', function (JoinClause $join) use ($tenantId, $organizationUnitId): void {
                $join->on('balances.item_id', '=', 'items.id')
                    ->where('balances.tenant_id', '=', $tenantId);
                $organizationUnitId === null
                    ? $join->whereNull('balances.organization_unit_id')
                    : $join->where('balances.organization_unit_id', '=', $organizationUnitId);
            })
            ->where('items.tenant_id', $tenantId)
            ->where('items.is_stockable', true)
            ->whereNull('items.deleted_at')
            ->select(['items.id', 'items.reorder_level'])
            ->selectRaw('COALESCE(SUM(balances.quantity_available), 0) as available')
            ->groupBy(['items.id', 'items.reorder_level'])
            ->get();

        $inStock = 0;
        $lowStock = 0;
        $outOfStock = 0;
        foreach ($items as $item) {
            $available = (string) $item->available;
            if ($this->math->compare($available, '0') <= 0) {
                $outOfStock++;

                continue;
            }
            $inStock++;
            if ($item->reorder_level !== null && $this->math->compare($available, (string) $item->reorder_level) <= 0) {
                $lowStock++;
            }
        }

        $expiring = DB::table('inventory_batches as batches')
            ->join('inventory_stock_balances as balances', 'balances.batch_id', '=', 'batches.id')
            ->where('batches.tenant_id', $tenantId)
            ->where('balances.tenant_id', $tenantId)
            ->where('batches.status', 'active')
            ->whereBetween('batches.expiry_date', [
                $today->toDateString(),
                $today->addDays(self::EXPIRING_BATCH_DAYS)->toDateString(),
            ])
            ->whereNull('batches.deleted_at');
        $this->organizationScope($expiring, 'batches.organization_unit_id', $organizationUnitId);
        $this->organizationScope($expiring, 'balances.organization_unit_id', $organizationUnitId);
        $expiringCount = $expiring
            ->groupBy('batches.id')
            ->havingRaw('SUM(balances.quantity_available) > 0')
            ->get(['batches.id'])
            ->count();

        return [
            'inventory_value' => $inventoryValue,
            'in_stock_items' => $inStock,
            'low_stock_items' => $lowStock,
            'out_of_stock_items' => $outOfStock,
            'expiring_batches' => $expiringCount,
        ];
    }

    /** @return array{total:string,overdue_count:int,buckets:list<array{key:string,label:string,count:int,amount:string}>} */
    private function aging(int $tenantId, ?int $organizationUnitId, InvoiceDirection $direction, CarbonImmutable $today): array
    {
        $definitions = [
            'current' => 'Current',
            '1_30' => '1–30 days',
            '31_60' => '31–60 days',
            '61_90' => '61–90 days',
            'over_90' => 'Over 90 days',
        ];
        $buckets = [];
        foreach ($definitions as $key => $label) {
            $buckets[$key] = ['key' => $key, 'label' => $label, 'count' => 0, 'amount' => '0.000000'];
        }

        $rows = $this->invoiceQuery($tenantId, $organizationUnitId, $direction)
            ->where('invoice_type', '!=', InvoiceType::Credit->value)
            ->where('balance_due', '>', 0)
            ->get(['due_date', 'balance_due']);
        $total = '0.000000';
        $overdueCount = 0;
        foreach ($rows as $row) {
            $amount = (string) $row->balance_due;
            $total = $this->math->add($total, $amount);
            $days = $row->due_date === null ? 0 : CarbonImmutable::parse((string) $row->due_date)->diffInDays($today, false);
            $key = $days <= 0 ? 'current' : ($days <= 30 ? '1_30' : ($days <= 60 ? '31_60' : ($days <= 90 ? '61_90' : 'over_90')));
            $buckets[$key]['count']++;
            $buckets[$key]['amount'] = $this->math->add($buckets[$key]['amount'], $amount);
            if ($days > 0) {
                $overdueCount++;
            }
        }

        return ['total' => $this->decimal($total), 'overdue_count' => $overdueCount, 'buckets' => array_values($buckets)];
    }

    private function activeServiceJobs(int $tenantId, ?int $organizationUnitId): int
    {
        $query = DB::table('vehicle_service_jobs')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', self::ACTIVE_SERVICE_JOB_STATUSES)
            ->whereNull('deleted_at');
        $this->organizationScope($query, 'organization_unit_id', $organizationUnitId);

        return $query->count();
    }

    private function overdueServiceJobs(int $tenantId, ?int $organizationUnitId, CarbonImmutable $today): int
    {
        $query = DB::table('vehicle_service_jobs')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', self::ACTIVE_SERVICE_JOB_STATUSES)
            ->whereDate('expected_delivery_date', '<', $today->toDateString())
            ->whereNull('deleted_at');
        $this->organizationScope($query, 'organization_unit_id', $organizationUnitId);

        return $query->count();
    }

    private function failedPayments(int $tenantId, ?int $organizationUnitId): int
    {
        $query = DB::table('payments')
            ->where('tenant_id', $tenantId)
            ->where('posting_status', PaymentPostingStatus::Failed->value)
            ->whereNull('deleted_at');
        $this->organizationScope($query, 'organization_unit_id', $organizationUnitId);

        return $query->count();
    }

    /** @return list<array{key:string,label:string,count:int,url:string,tone:string}> */
    private function actions(int $overdueReceivables, int $overduePayables, int $overdueJobs, int $lowStock, int $expiringBatches, int $failedPayments): array
    {
        return array_values(array_filter([
            ['key' => 'failed_payments', 'label' => 'Failed payment postings', 'count' => $failedPayments, 'url' => '/payments?posting_status=failed', 'tone' => 'critical'],
            ['key' => 'overdue_receivables', 'label' => 'Overdue customer invoices', 'count' => $overdueReceivables, 'url' => '/invoices', 'tone' => 'warning'],
            ['key' => 'overdue_payables', 'label' => 'Overdue supplier invoices', 'count' => $overduePayables, 'url' => '/reports/purchase/grn-payables', 'tone' => 'warning'],
            ['key' => 'overdue_jobs', 'label' => 'Overdue active service jobs', 'count' => $overdueJobs, 'url' => '/vehicle-service/jobs', 'tone' => 'warning'],
            ['key' => 'low_stock', 'label' => 'Items below reorder level', 'count' => $lowStock, 'url' => '/inventory?tab=availability', 'tone' => 'info'],
            ['key' => 'expiring_batches', 'label' => 'Batches expiring within 30 days', 'count' => $expiringBatches, 'url' => '/inventory?tab=tracking', 'tone' => 'info'],
        ], static fn (array $action): bool => $action['count'] > 0));
    }

    private function invoiceQuery(int $tenantId, ?int $organizationUnitId, InvoiceDirection $direction): Builder
    {
        $query = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->where('direction', $direction->value)
            ->whereIn('status', self::FINAL_INVOICE_STATUSES)
            ->whereNull('deleted_at');
        $this->organizationScope($query, 'organization_unit_id', $organizationUnitId);

        return $query;
    }

    /** @param array<string, string> $values @return array<string, array<string, string>> */
    private function monthlyBuckets(string $dateFrom, string $dateTo, array $values): array
    {
        $cursor = CarbonImmutable::parse($dateFrom)->startOfMonth();
        $end = CarbonImmutable::parse($dateTo)->startOfMonth();
        $buckets = [];
        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->format('Y-m');
            $buckets[$key] = ['key' => $key, 'label' => $cursor->format('M Y'), ...$values];
            $cursor = $cursor->addMonth();
        }

        return $buckets;
    }

    private function organizationScope(Builder $query, string $column, ?int $organizationUnitId): void
    {
        $organizationUnitId === null ? $query->whereNull($column) : $query->where($column, $organizationUnitId);
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? '0'));
    }
}
