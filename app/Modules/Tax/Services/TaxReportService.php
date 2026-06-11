<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Tax\Models\TaxDocumentSnapshot;
use Modules\Tax\Models\TaxTransaction;

final class TaxReportService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function transactions(int $tenantId, ?int $organizationUnitId, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->transactionQuery($tenantId, $organizationUnitId, $filters)
            ->with(['tax', 'account'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(string $report, int $tenantId, ?int $organizationUnitId, array $filters = []): array
    {
        return match ($report) {
            'summary' => $this->summary($tenantId, $organizationUnitId, $filters),
            'liability' => $this->summary($tenantId, $organizationUnitId, array_merge($filters, ['payable' => true])),
            'receivable' => $this->summary($tenantId, $organizationUnitId, array_merge($filters, ['receivable' => true])),
            'vat' => $this->summary($tenantId, $organizationUnitId, array_merge($filters, ['tax_type' => 'VAT'])),
            'wht' => $this->summary($tenantId, $organizationUnitId, array_merge($filters, ['is_withholding' => true])),
            'reconciliation' => $this->reconciliation($tenantId, $organizationUnitId, $filters),
            default => $this->summary($tenantId, $organizationUnitId, $filters),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, string>}
     */
    public function summary(int $tenantId, ?int $organizationUnitId, array $filters = []): array
    {
        $rows = $this->transactionQuery($tenantId, $organizationUnitId, $filters)->get();
        $grouped = $rows->groupBy(fn (TaxTransaction $row): string => $row->tax_code.'|'.$row->tax_type);
        $summaryRows = [];
        $totalTaxable = '0.000000';
        $totalTax = '0.000000';
        $totalWithholding = '0.000000';

        foreach ($grouped as $groupRows) {
            /** @var Collection<int, TaxTransaction> $groupRows */
            $first = $groupRows->first();
            if (! $first instanceof TaxTransaction) {
                continue;
            }

            $taxable = $this->sum($groupRows, 'taxable_amount');
            $tax = $this->sum($groupRows, 'tax_amount');
            $withholding = $this->sum($groupRows, 'withholding_amount');
            $summaryRows[] = [
                'tax_code' => $first->tax_code,
                'tax_name' => $first->tax_name,
                'tax_type' => $first->tax_type,
                'taxable_amount' => $taxable,
                'tax_amount' => $tax,
                'withholding_amount' => $withholding,
                'transaction_count' => $groupRows->count(),
            ];
            $totalTaxable = $this->math->add($totalTaxable, $taxable);
            $totalTax = $this->math->add($totalTax, $tax);
            $totalWithholding = $this->math->add($totalWithholding, $withholding);
        }

        return [
            'rows' => array_values($summaryRows),
            'totals' => [
                'taxable_amount' => $totalTaxable,
                'tax_amount' => $totalTax,
                'withholding_amount' => $totalWithholding,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, string>}
     */
    public function reconciliation(int $tenantId, ?int $organizationUnitId, array $filters = []): array
    {
        $snapshots = $this->snapshotQuery($tenantId, $organizationUnitId, $filters)->get()->groupBy('tax_code');
        $transactions = $this->transactionQuery($tenantId, $organizationUnitId, $filters)->get()->groupBy('tax_code');
        $codes = collect($snapshots->keys())->merge($transactions->keys())->unique()->values();
        $rows = [];
        $snapshotTotal = '0.000000';
        $transactionTotal = '0.000000';
        $differenceTotal = '0.000000';

        foreach ($codes as $code) {
            $snapshotAmount = $this->sum($snapshots->get($code, collect()), 'tax_amount');
            $transactionAmount = $this->sum($transactions->get($code, collect()), 'tax_amount');
            $difference = $this->math->sub($snapshotAmount, $transactionAmount);
            $rows[] = [
                'tax_code' => $code,
                'snapshot_tax_amount' => $snapshotAmount,
                'transaction_tax_amount' => $transactionAmount,
                'difference' => $difference,
            ];
            $snapshotTotal = $this->math->add($snapshotTotal, $snapshotAmount);
            $transactionTotal = $this->math->add($transactionTotal, $transactionAmount);
            $differenceTotal = $this->math->add($differenceTotal, $difference);
        }

        return [
            'rows' => $rows,
            'totals' => [
                'snapshot_tax_amount' => $snapshotTotal,
                'transaction_tax_amount' => $transactionTotal,
                'difference' => $differenceTotal,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function transactionQuery(int $tenantId, ?int $organizationUnitId, array $filters): Builder
    {
        $query = TaxTransaction::query()->where('tenant_id', $tenantId);
        $this->scopeOrganization($query, $this->filterOrg($organizationUnitId, $filters));
        $this->filters($query, $filters, 'transaction_date');

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function snapshotQuery(int $tenantId, ?int $organizationUnitId, array $filters): Builder
    {
        $query = TaxDocumentSnapshot::query()->where('tenant_id', $tenantId);
        $this->scopeOrganization($query, $this->filterOrg($organizationUnitId, $filters));
        $this->filters($query, $filters, 'source_date');

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filters(Builder $query, array $filters, string $dateColumn): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($dateColumn, '>=', (string) $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate($dateColumn, '<=', (string) $filters['date_to']);
        }
        foreach (['tax_type', 'tax_code'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        foreach (['payable', 'receivable', 'recoverable', 'is_withholding'] as $field) {
            if (array_key_exists($field, $filters)) {
                $query->where($field, (bool) $filters[$field]);
            }
        }
        if (! empty($filters['customer_id'])) {
            $query->where('party_type', 'customer')->where('party_id', (int) $filters['customer_id']);
        }
        if (! empty($filters['supplier_id'])) {
            $query->where('party_type', 'supplier')->where('party_id', (int) $filters['supplier_id']);
        }
    }

    private function scopeOrganization(Builder $query, ?int $organizationUnitId): void
    {
        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filterOrg(?int $organizationUnitId, array $filters): ?int
    {
        return isset($filters['organization_unit_id']) && $filters['organization_unit_id'] !== ''
            ? (int) $filters['organization_unit_id']
            : $organizationUnitId;
    }

    /**
     * @param  iterable<object>  $rows
     */
    private function sum(iterable $rows, string $column): string
    {
        $total = '0.000000';
        foreach ($rows as $row) {
            $total = $this->math->add($total, (string) $row->{$column});
        }

        return $total;
    }
}
