<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductSearchBenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_search_benchmark_command_runs_successfully_with_seeded_data(): void
    {
        $tenantId = 777;
        $this->seedTenantAndCatalogData($tenantId);

        $this->artisan('product:benchmark-search', [
            '--tenant_id' => (string) $tenantId,
            '--warehouse_id' => '701',
            '--iterations' => '2',
            '--per_page' => '10',
            '--include_pricing' => '0',
            '--term' => ['SKU-777-001', 'BC-777-00001'],
        ])->assertExitCode(0);
    }

    public function test_product_search_benchmark_command_supports_json_format(): void
    {
        $tenantId = 778;
        $this->seedTenantAndCatalogData($tenantId);

        $exitCode = Artisan::call('product:benchmark-search', [
            '--tenant_id' => (string) $tenantId,
            '--warehouse_id' => '701',
            '--iterations' => '2',
            '--per_page' => '10',
            '--include_pricing' => '0',
            '--format' => 'json',
            '--term' => ['SKU-777-001', 'BC-777-00001'],
        ]);

        $this->assertSame(0, $exitCode);

        /** @var array<string, mixed> $payload */
        $payload = json_decode(trim(Artisan::output()), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('success', $payload['status']);
        $this->assertSame($tenantId, $payload['tenant_id']);
        $this->assertCount(2, $payload['results']);
        $this->assertSame('SKU-777-001', $payload['results'][0]['term']);
        $this->assertArrayHasKey('p95_ms', $payload['results'][0]);
    }

    private function seedTenantAndCatalogData(int $tenantId): void
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

        DB::table('units_of_measure')->insert([
            'id' => 7001,
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
            'id' => 7002,
            'tenant_id' => $tenantId,
            'parent_id' => null,
            'name' => 'Bench Cat',
            'slug' => 'bench-cat',
            'code' => 'B-CAT',
            'path' => '/bench-cat',
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
            'id' => 7003,
            'tenant_id' => $tenantId,
            'parent_id' => null,
            'name' => 'Bench Brand',
            'slug' => 'bench-brand',
            'code' => 'B-BRAND',
            'path' => '/bench-brand',
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

        DB::table('warehouses')->insert([
            'id' => 701,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'name' => 'Bench Warehouse',
            'code' => 'B-WH',
            'image_path' => null,
            'type' => 'standard',
            'address_id' => null,
            'is_active' => true,
            'is_default' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouse_locations')->insert([
            'id' => 702,
            'tenant_id' => $tenantId,
            'warehouse_id' => 701,
            'name' => 'Bench Rack',
            'code' => 'B-R1',
            'type' => 'rack',
            'parent_id' => null,
            'path' => '/B-R1',
            'depth' => 1,
            'is_pickable' => true,
            'is_active' => true,
            'is_receivable' => true,
            'capacity' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'id' => 7004,
            'tenant_id' => $tenantId,
            'category_id' => 7002,
            'brand_id' => 7003,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Bench Product',
            'slug' => 'bench-product',
            'sku' => 'SKU-'.$tenantId.'-001',
            'description' => null,
            'image_path' => null,
            'base_uom_id' => 7001,
            'purchase_uom_id' => 7001,
            'sales_uom_id' => 7001,
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
        ]);

        DB::table('product_identifiers')->insert([
            'id' => 7005,
            'tenant_id' => $tenantId,
            'product_id' => 7004,
            'variant_id' => null,
            'batch_id' => null,
            'serial_id' => null,
            'technology' => 'barcode_1d',
            'format' => 'code128',
            'value' => 'BC-'.$tenantId.'-00001',
            'gs1_company_prefix' => null,
            'gs1_application_identifiers' => null,
            'is_primary' => true,
            'is_active' => true,
            'format_config' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('stock_levels')->insert([
            'tenant_id' => $tenantId,
            'product_id' => 7004,
            'variant_id' => null,
            'location_id' => 702,
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => 7001,
            'quantity_on_hand' => '25.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost' => '10.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
