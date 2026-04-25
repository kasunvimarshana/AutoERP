<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Product\Application\Contracts\SearchProductCatalogServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('performance')]
class ProductCatalogSearchPerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    private SearchProductCatalogServiceInterface $searchProductCatalogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchProductCatalogService = app(SearchProductCatalogServiceInterface::class);
    }

    public function test_search_benchmark_stays_within_guard_rail_on_high_cardinality_dataset(): void
    {
        $tenantId = 9901;
        $this->seedTenant($tenantId);
        $this->seedReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Perf Warehouse',
            'code' => 'PERF-WH',
            'is_default' => true,
        ]);

        $location = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Perf Rack',
            'code' => 'PERF-R1',
            'type' => 'rack',
        ]);

        $this->seedCatalogDataset($tenantId, $location->getId(), 1200);

        // Warm up query planner/cache path before collecting timings.
        $this->searchProductCatalogService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'term' => 'Performance Product 1',
            'include_pricing' => false,
            'per_page' => 25,
        ]);

        $timings = [];
        $scenarios = [
            ['label' => 'identifier', 'term' => 'BC-01050'],
            ['label' => 'name', 'term' => 'Performance Product 10'],
            ['label' => 'sku', 'term' => 'PerfSKU-009'],
        ];

        foreach ($scenarios as $scenario) {
            $startedAt = microtime(true);

            $payload = $this->searchProductCatalogService->execute([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouse->getId(),
                'term' => $scenario['term'],
                'stock_status' => 'in_stock',
                'include_pricing' => false,
                'per_page' => 25,
                'page' => 1,
                'sort' => 'name:asc',
            ]);

            $elapsedMs = (microtime(true) - $startedAt) * 1000;
            $timings[$scenario['label']] = $elapsedMs;

            $this->assertGreaterThanOrEqual(1, $payload['meta']['total']);
            $this->assertNotEmpty($payload['data']);
        }

        $maxMs = max($timings);
        $avgMs = array_sum($timings) / count($timings);

        fwrite(
            STDERR,
            sprintf(
                "\nProduct search benchmark (ms): identifier=%.2f, name=%.2f, sku=%.2f, avg=%.2f, max=%.2f\n",
                $timings['identifier'],
                $timings['name'],
                $timings['sku'],
                $avgMs,
                $maxMs,
            )
        );

        // Guard rail is intentionally generous to avoid flakiness across environments.
        $this->assertLessThan(15000.0, $maxMs);
    }

    private function seedTenant(int $tenantId): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant '.$tenantId,
            'slug' => 'tenant-'.$tenantId,
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedReferenceData(int $tenantId): void
    {
        DB::table('units_of_measure')->insert([
            'id' => 990101,
            'tenant_id' => $tenantId,
            'name' => 'Each',
            'symbol' => 'EA',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('product_categories')->insert([
            'id' => 990201,
            'tenant_id' => $tenantId,
            'parent_id' => null,
            'name' => 'Performance Category',
            'slug' => 'performance-category',
            'code' => 'PERF-CAT',
            'path' => '/performance-category',
            'image_path' => null,
            'depth' => 0,
            'is_active' => true,
            'description' => null,
            'attributes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('product_brands')->insert([
            'id' => 990301,
            'tenant_id' => $tenantId,
            'parent_id' => null,
            'name' => 'Performance Brand',
            'slug' => 'performance-brand',
            'code' => 'PERF-BRAND',
            'path' => '/performance-brand',
            'image_path' => null,
            'depth' => 0,
            'is_active' => true,
            'website' => null,
            'description' => null,
            'attributes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedCatalogDataset(int $tenantId, int $locationId, int $count): void
    {
        $products = [];
        $identifiers = [];
        $stockLevels = [];

        for ($i = 1; $i <= $count; $i++) {
            $id = 991000 + $i;
            $sku = sprintf('PerfSKU-%04d', $i);
            $identifier = sprintf('BC-%05d', $i);

            $products[] = [
                'id' => $id,
                'tenant_id' => $tenantId,
                'category_id' => 990201,
                'brand_id' => 990301,
                'org_unit_id' => null,
                'type' => 'physical',
                'name' => 'Performance Product '.$i,
                'slug' => 'performance-product-'.$i,
                'sku' => $sku,
                'description' => null,
                'image_path' => null,
                'base_uom_id' => 990101,
                'purchase_uom_id' => 990101,
                'sales_uom_id' => 990101,
                'tax_group_id' => null,
                'uom_conversion_factor' => '1.0000000000',
                'is_batch_tracked' => false,
                'is_lot_tracked' => false,
                'is_serial_tracked' => false,
                'valuation_method' => 'fifo',
                'standard_cost' => '10.000000',
                'income_account_id' => null,
                'cogs_account_id' => null,
                'inventory_account_id' => null,
                'expense_account_id' => null,
                'is_active' => true,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ];

            $identifiers[] = [
                'id' => 992000 + $i,
                'tenant_id' => $tenantId,
                'product_id' => $id,
                'variant_id' => null,
                'batch_id' => null,
                'serial_id' => null,
                'technology' => 'barcode_1d',
                'format' => 'code128',
                'value' => $identifier,
                'gs1_company_prefix' => null,
                'gs1_application_identifiers' => null,
                'is_primary' => true,
                'is_active' => true,
                'format_config' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ];

            $stockLevels[] = [
                'tenant_id' => $tenantId,
                'product_id' => $id,
                'variant_id' => null,
                'location_id' => $locationId,
                'batch_id' => null,
                'serial_id' => null,
                'uom_id' => 990101,
                'quantity_on_hand' => '100.000000',
                'quantity_reserved' => '0.000000',
                'unit_cost' => '10.000000',
                'last_movement_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($products, 300) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        foreach (array_chunk($identifiers, 300) as $chunk) {
            DB::table('product_identifiers')->insert($chunk);
        }

        foreach (array_chunk($stockLevels, 300) as $chunk) {
            DB::table('stock_levels')->insert($chunk);
        }
    }
}
