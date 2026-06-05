<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\UOM\Application\Contracts\Services\UomUsageSummaryServiceInterface;

final class DatabaseUomUsageSummaryService implements UomUsageSummaryServiceInterface
{
    /**
     * @return array<string, int>
     */
    public function summarize(int $unitId, int $tenantId): array
    {
        return [
            'items' => $this->countAny('items', $tenantId, $unitId, [
                'base_uom_id',
                'default_receipt_uom_id',
                'default_issue_uom_id',
                'default_consumption_uom_id',
                'default_charge_uom_id',
            ]),
            'inventory' => $this->countInventory($tenantId, $unitId),
            'purchase' => $this->countAnyAcrossTables($tenantId, $unitId, [
                'purchase_order_lines' => ['uom_id'],
                'grn_lines' => ['uom_id'],
                'purchase_return_lines' => ['uom_id'],
            ]),
            'sales' => $this->countAnyAcrossTables($tenantId, $unitId, [
                'sales_order_lines' => ['uom_id'],
                'gdn_lines' => ['uom_id'],
                'sales_return_lines' => ['uom_id'],
            ]),
            'service' => $this->countAnyAcrossTables($tenantId, $unitId, [
                'vehicle_service_job_card_lines' => ['uom_id'],
                'vehicle_service_labor_items' => ['uom_id'],
                'vehicle_service_non_inventory_items' => ['uom_id'],
                'vehicle_service_job_external_services' => ['uom_id'],
                'vehicle_service_job_customer_supplied_items' => ['uom_id'],
            ]),
            'rental' => $this->countAnyAcrossTables($tenantId, $unitId, [
                'vehicle_rental_agreement_lines' => ['uom_id'],
                'vehicle_rental_agreement_rates' => ['billing_uom_id'],
                'vehicle_rental_rate_rules' => ['threshold_uom_id'],
                'vehicle_rental_extra_charges' => ['uom_id'],
            ]),
            'conversions_from' => $this->countAny('uom_conversions', $tenantId, $unitId, ['from_uom_id']),
            'conversions_to' => $this->countAny('uom_conversions', $tenantId, $unitId, ['to_uom_id']),
        ];
    }

    /**
     * @param array<string, list<string>> $tables
     */
    private function countAnyAcrossTables(int $tenantId, int $unitId, array $tables): int
    {
        $total = 0;

        foreach ($tables as $table => $columns) {
            $total += $this->countAny($table, $tenantId, $unitId, $columns);
        }

        return $total;
    }

    /**
     * @param list<string> $columns
     */
    private function countAny(string $table, int $tenantId, int $unitId, array $columns): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
            return 0;
        }

        $availableColumns = array_values(array_filter(
            $columns,
            static fn (string $column): bool => Schema::hasColumn($table, $column),
        ));

        if ($availableColumns === []) {
            return 0;
        }

        return (int) DB::table($table)
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($availableColumns, $unitId): void {
                foreach ($availableColumns as $index => $column) {
                    $index === 0
                        ? $query->where($column, $unitId)
                        : $query->orWhere($column, $unitId);
                }
            })
            ->count();
    }

    private function countInventory(int $tenantId, int $unitId): int
    {
        return $this->countAnyAcrossTables($tenantId, $unitId, [
            'stock_levels' => ['base_uom_id'],
            'stock_movements' => ['transaction_uom_id', 'base_uom_id'],
            'stock_reservations' => ['transaction_uom_id', 'base_uom_id'],
            'stock_transfer_lines' => ['uom_id'],
            'stock_adjustment_lines' => ['transaction_uom_id', 'base_uom_id'],
            'cycle_count_lines' => ['transaction_uom_id', 'base_uom_id'],
            'transfer_order_lines' => ['uom_id'],
            'receipt_inspections' => ['transaction_uom_id', 'base_uom_id'],
            'put_away_tasks' => ['transaction_uom_id', 'base_uom_id'],
            'picking_tasks' => ['transaction_uom_id', 'base_uom_id'],
        ]);
    }
}
