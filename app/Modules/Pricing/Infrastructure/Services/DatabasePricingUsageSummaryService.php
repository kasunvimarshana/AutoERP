<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Pricing\Application\Contracts\Services\PricingUsageSummaryServiceInterface;

final class DatabasePricingUsageSummaryService implements PricingUsageSummaryServiceInterface
{
    public function summarizePriceList(int $priceListId, int $tenantId): array
    {
        return [
            'price_list_id' => $priceListId,
            'counts' => [
                'price_list_items' => $this->count('price_list_items', 'price_list_id', $priceListId, $tenantId),
                'customer_links' => $this->count('customer_price_lists', 'price_list_id', $priceListId, $tenantId),
                'supplier_links' => $this->count('supplier_price_lists', 'price_list_id', $priceListId, $tenantId),
                'tiers' => $this->countTiersForPriceList($priceListId, $tenantId),
                'purchase_references' => $this->countReferences($tenantId, 'price_list_id', $priceListId, [
                    'purchase_orders',
                    'grn_headers',
                ]),
                'sales_references' => $this->countReferences($tenantId, 'price_list_id', $priceListId, [
                    'sales_orders',
                    'gdn_headers',
                ]),
                'service_references' => $this->countReferences($tenantId, 'price_list_id', $priceListId, [
                    'vehicle_service_job_cards',
                ]),
                'rental_references' => $this->countReferences($tenantId, 'price_list_id', $priceListId, [
                    'vehicle_rental_settings',
                    'vehicle_rental_agreements',
                ]),
                'history_entries' => $this->countHistory('pricing', $priceListId, $tenantId),
            ],
        ];
    }

    public function summarizePricingRule(int $pricingRuleId, int $tenantId): array
    {
        return [
            'pricing_rule_id' => $pricingRuleId,
            'counts' => [
                'conditions' => $this->count('pricing_rule_conditions', 'pricing_rule_id', $pricingRuleId, $tenantId),
                'tiers' => $this->count('pricing_tiers', 'pricing_rule_id', $pricingRuleId, $tenantId),
                'history_entries' => $this->countHistory('pricing_rule', $pricingRuleId, $tenantId),
            ],
        ];
    }

    /**
     * @param list<string> $tables
     */
    private function countReferences(int $tenantId, string $column, int $id, array $tables): int
    {
        $count = 0;

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $query = DB::table($table)->where($column, $id);
            if (Schema::hasColumn($table, 'tenant_id')) {
                $query->where('tenant_id', $tenantId);
            }

            $count += (int) $query->count();
        }

        return $count;
    }

    private function count(string $table, string $column, int $id, int $tenantId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        $query = DB::table($table)->where($column, $id);
        if (Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        return (int) $query->count();
    }

    private function countTiersForPriceList(int $priceListId, int $tenantId): int
    {
        if (! Schema::hasTable('pricing_tiers') || ! Schema::hasTable('price_list_items')) {
            return 0;
        }

        return (int) DB::table('pricing_tiers')
            ->join('price_list_items', 'pricing_tiers.price_list_item_id', '=', 'price_list_items.id')
            ->where('pricing_tiers.tenant_id', $tenantId)
            ->where('price_list_items.price_list_id', $priceListId)
            ->count();
    }

    private function countHistory(string $entityType, int $entityId, int $tenantId): int
    {
        if (! Schema::hasTable('price_histories')) {
            return 0;
        }

        return (int) DB::table('price_histories')
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->count();
    }
}
