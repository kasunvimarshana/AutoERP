<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;

final class FinanceTaxReportService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @return array<string, mixed>
     */
    public function liability(
        int $tenantId,
        ?int $organizationUnitId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        return $this->summary($tenantId, $organizationUnitId, ['payable' => true], $dateFrom, $dateTo);
    }

    /**
     * @return array<string, mixed>
     */
    public function reconciliation(
        int $tenantId,
        ?int $organizationUnitId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        return $this->summary($tenantId, $organizationUnitId, [], $dateFrom, $dateTo);
    }

    /**
     * @param  array<string, mixed>  $constraints
     * @return array<string, mixed>
     */
    private function summary(
        int $tenantId,
        ?int $organizationUnitId,
        array $constraints,
        ?string $dateFrom,
        ?string $dateTo,
    ): array {
        $query = DB::table('tax_transactions')
            ->where('tenant_id', $tenantId)
            ->orderBy('transaction_date')
            ->orderBy('id');

        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
        if ($dateFrom !== null) {
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }
        foreach ($constraints as $field => $value) {
            $query->where($field, $value);
        }

        $taxable = '0.000000';
        $tax = '0.000000';
        $withholding = '0.000000';
        $rows = [];

        foreach ($query->get() as $row) {
            $taxable = $this->math->add($taxable, (string) $row->taxable_amount);
            $tax = $this->math->add($tax, (string) $row->tax_amount);
            $withholding = $this->math->add($withholding, (string) $row->withholding_amount);
            $rows[] = [
                'transaction_date' => (string) $row->transaction_date,
                'tax_code' => (string) $row->tax_code,
                'tax_name' => (string) $row->tax_name,
                'tax_type' => (string) $row->tax_type,
                'source_module' => $row->source_module,
                'source_type' => $row->source_type,
                'source_number' => $row->source_number,
                'taxable_amount' => (string) $row->taxable_amount,
                'tax_amount' => (string) $row->tax_amount,
                'withholding_amount' => (string) $row->withholding_amount,
                'payable' => (bool) $row->payable,
                'receivable' => (bool) $row->receivable,
            ];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_taxable_amount' => $taxable,
            'total_tax_amount' => $tax,
            'total_withholding_amount' => $withholding,
            'net_tax_amount' => $this->math->sub($tax, $withholding),
            'rows' => $rows,
        ];
    }
}
