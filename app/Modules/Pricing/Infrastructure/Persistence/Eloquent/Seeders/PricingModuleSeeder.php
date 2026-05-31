<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class PricingModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('price_lists') || ! Schema::hasTable('price_list_items')) {
            return;
        }

        DB::transaction(function (): void {
            $tenantId = $this->defaultTenantId();
            $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);
            $currencyId = $this->currencyId();
            $uoms = $this->uoms($tenantId);
            $items = $this->items($tenantId);

            if ($items === [] || $uoms === []) {
                return;
            }

            $now = now();
            $priceLists = [
                ['code' => 'PL-SALES-STD', 'name' => 'Standard Sales Price List', 'type' => 'sales', 'scope_type' => 'generic', 'priority' => 100],
                ['code' => 'PL-PURCHASE-SUP', 'name' => 'Supplier Purchase Price List', 'type' => 'purchase', 'scope_type' => 'supplier', 'priority' => 90],
                ['code' => 'PL-SERVICE-STD', 'name' => 'Service Price List', 'type' => 'service', 'scope_type' => 'generic', 'priority' => 80],
                ['code' => 'PL-RENTAL-STD', 'name' => 'Rental Price List', 'type' => 'rental', 'scope_type' => 'generic', 'priority' => 70],
            ];

            foreach ($priceLists as $list) {
                DB::table('price_lists')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'code' => $list['code']],
                    [
                        'currency_id' => $currencyId,
                        'description' => 'Seeded pricing data for real module testing.',
                        'is_active' => true,
                        'is_default' => $list['code'] === 'PL-SALES-STD',
                        'is_exclusive' => false,
                        'is_stackable' => true,
                        'metadata' => $this->json(['module_usage' => [$list['type']], 'seed_source' => 'pricing_module']),
                        'name' => $list['name'],
                        'organization_unit_id' => $organizationUnitId,
                        'priority' => $list['priority'],
                        'row_version' => 1,
                        'scope_type' => $list['scope_type'],
                        'source_id' => null,
                        'source_type' => null,
                        'type' => $list['type'],
                        'updated_at' => $now,
                        'created_at' => $now,
                        'valid_from' => now()->startOfYear()->toDateString(),
                        'valid_to' => now()->endOfYear()->toDateString(),
                    ],
                );
            }

            $this->seedPriceItem($tenantId, $organizationUnitId, 'PL-SALES-STD', $items[0], $uoms['PCS'] ?? reset($uoms), $currencyId, '18500.0000');
            $this->seedPriceItem($tenantId, $organizationUnitId, 'PL-PURCHASE-SUP', $items[0], $uoms['PCS'] ?? reset($uoms), $currencyId, '12500.0000');
            $this->seedPriceItem($tenantId, $organizationUnitId, 'PL-SERVICE-STD', $items[1] ?? $items[0], $uoms['HOUR'] ?? reset($uoms), $currencyId, '4500.0000');
            $this->seedPriceItem($tenantId, $organizationUnitId, 'PL-RENTAL-STD', $items[array_key_last($items)], $uoms['DAY'] ?? reset($uoms), $currencyId, '22000.0000');

            $salesItemId = $this->priceItemId($tenantId, 'PL-SALES-STD', $items[0]);
            if ($salesItemId !== null) {
                DB::table('pricing_tiers')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'price_list_item_id' => $salesItemId, 'sequence' => 1],
                    [
                        'adjustment_type' => 'percentage',
                        'adjustment_value' => '-5.0000',
                        'currency_id' => $currencyId,
                        'discount_id' => null,
                        'is_active' => true,
                        'max_quantity' => '24.0000',
                        'min_quantity' => '10.0000',
                        'organization_unit_id' => $organizationUnitId,
                        'price' => null,
                        'pricing_rule_id' => null,
                        'priority' => 10,
                        'row_version' => 1,
                        'uom_id' => $uoms['PCS'] ?? reset($uoms),
                        'updated_at' => $now,
                        'created_at' => $now,
                        'valid_from' => null,
                        'valid_to' => null,
                    ],
                );
            }

            DB::table('discounts')->updateOrInsert(
                ['tenant_id' => $tenantId, 'code' => 'DISC-BULK-STD'],
                [
                    'currency_id' => $currencyId,
                    'customer_id' => null,
                    'description' => 'Standard quantity discount for resolver testing.',
                    'discount_type' => 'percentage',
                    'discount_value' => '2.5000',
                    'is_active' => true,
                    'is_exclusive' => false,
                    'is_stackable' => true,
                    'item_id' => null,
                    'max_quantity' => null,
                    'metadata' => $this->json(['seed_source' => 'pricing_module']),
                    'min_quantity' => '5.0000',
                    'name' => 'Standard Bulk Discount',
                    'organization_unit_id' => $organizationUnitId,
                    'priority' => 20,
                    'row_version' => 1,
                    'source_id' => null,
                    'source_type' => null,
                    'supplier_id' => null,
                    'uom_id' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                    'valid_from' => now()->startOfYear()->toDateString(),
                    'valid_to' => now()->endOfYear()->toDateString(),
                ],
            );

            DB::table('pricing_rules')->updateOrInsert(
                ['tenant_id' => $tenantId, 'code' => 'RULE-STANDARD-PRIORITY'],
                [
                    'action_type' => 'adjust_price',
                    'action_value' => '0.0000',
                    'applies_to_type' => 'price_resolve',
                    'currency_id' => $currencyId,
                    'customer_id' => null,
                    'description' => 'Generic resolver rule used for real Pricing UI testing.',
                    'is_active' => true,
                    'is_exclusive' => false,
                    'is_stackable' => true,
                    'item_id' => null,
                    'max_quantity' => null,
                    'metadata' => $this->json(['rule_type' => 'price_resolve', 'seed_source' => 'pricing_module']),
                    'min_quantity' => null,
                    'name' => 'Standard Priority Rule',
                    'organization_unit_id' => $organizationUnitId,
                    'priority' => 50,
                    'row_version' => 1,
                    'source_id' => null,
                    'source_type' => null,
                    'supplier_id' => null,
                    'uom_id' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                    'valid_from' => now()->startOfYear()->toDateString(),
                    'valid_to' => now()->endOfYear()->toDateString(),
                ],
            );
        }, 3);
    }

    private function seedPriceItem(int $tenantId, ?int $organizationUnitId, string $priceListCode, int $itemId, int $uomId, ?int $currencyId, string $price): void
    {
        $priceListId = DB::table('price_lists')->where('tenant_id', $tenantId)->where('code', $priceListCode)->value('id');
        if ($priceListId === null) {
            return;
        }

        DB::table('price_list_items')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'price_list_id' => $priceListId,
                'item_id' => $itemId,
                'variant_id' => null,
                'uom_id' => $uomId,
                'currency_id' => $currencyId,
                'party_type' => null,
                'party_id' => null,
                'source_type' => null,
                'source_id' => null,
                'min_quantity' => '1.0000',
                'max_quantity' => null,
            ],
            [
                'discount_type' => 'percentage',
                'discount_value' => '0.0000',
                'is_active' => true,
                'is_promotional' => false,
                'is_tax_inclusive' => false,
                'markup_type' => null,
                'markup_value' => '0.0000',
                'metadata' => $this->json(['seed_source' => 'pricing_module']),
                'organization_unit_id' => $organizationUnitId,
                'price' => $price,
                'priority' => 0,
                'reference' => 'seed',
                'row_version' => 1,
                'updated_at' => now(),
                'created_at' => now(),
                'valid_from' => now()->startOfYear()->toDateString(),
                'valid_to' => now()->endOfYear()->toDateString(),
            ],
        );
    }

    private function defaultTenantId(): int
    {
        $code = strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')));
        $id = DB::table('tenants')->where('code', $code)->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        if ($id === null) {
            throw new RuntimeException('Seed a tenant before running the Pricing module seeder.');
        }

        return (int) $id;
    }

    private function defaultOrganizationUnitId(int $tenantId): ?int
    {
        return DB::table('organization_units')->where('tenant_id', $tenantId)->orderByDesc('is_active')->orderBy('id')->value('id');
    }

    private function currencyId(): ?int
    {
        return DB::table('currencies')->where('code', env('AUTH_LOCAL_TENANT_CURRENCY', 'LKR'))->value('id')
            ?? DB::table('currencies')->orderBy('id')->value('id');
    }

    /**
     * @return array<int, int>
     */
    private function items(int $tenantId): array
    {
        return DB::table('items')->where('tenant_id', $tenantId)->orderBy('id')->limit(6)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return array<string, int>
     */
    private function uoms(int $tenantId): array
    {
        return DB::table('unit_of_measures')->where('tenant_id', $tenantId)->pluck('id', 'code')->map(fn ($id) => (int) $id)->all();
    }

    private function priceItemId(int $tenantId, string $priceListCode, int $itemId): ?int
    {
        $id = DB::table('price_list_items')
            ->join('price_lists', 'price_list_items.price_list_id', '=', 'price_lists.id')
            ->where('price_list_items.tenant_id', $tenantId)
            ->where('price_lists.code', $priceListCode)
            ->where('price_list_items.item_id', $itemId)
            ->value('price_list_items.id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload): string
    {
        return (string) json_encode($payload, JSON_THROW_ON_ERROR);
    }
}
