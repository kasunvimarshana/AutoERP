<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Product\Domain\Entities\ProductIdentifier;
use Modules\Product\Domain\RepositoryInterfaces\ProductIdentifierRepositoryInterface;
use Tests\TestCase;

class ProductIdentifierRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAccountsTableExists();
        $this->ensureBatchesAndSerialsTablesExist();
        $this->seedReferenceData();
    }

    public function test_save_creates_and_updates_product_identifier(): void
    {
        /** @var ProductIdentifierRepositoryInterface $repository */
        $repository = app(ProductIdentifierRepositoryInterface::class);

        $created = $repository->save(new ProductIdentifier(
            tenantId: 91,
            productId: 9001,
            variantId: 9101,
            technology: 'barcode_1d',
            format: 'code128',
            value: 'ABC-100',
            isPrimary: true,
            isActive: true,
            metadata: ['source' => 'integration-test'],
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('ABC-100', $created->getValue());

        $updated = $repository->save(new ProductIdentifier(
            id: $created->getId(),
            tenantId: 91,
            productId: 9001,
            variantId: 9101,
            technology: 'barcode_2d',
            format: 'datamatrix',
            value: 'ABC-100-UPD',
            isPrimary: false,
            isActive: true,
            metadata: ['source' => 'integration-test', 'updated' => true],
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('barcode_2d', $updated->getTechnology());
        $this->assertSame('ABC-100-UPD', $updated->getValue());
    }

    public function test_find_by_tenant_and_value_returns_domain_entity(): void
    {
        $this->insertIdentifierRow(id: 9501, tenantId: 91, productId: 9001, value: 'VAL-001', technology: 'barcode_1d', format: 'code128');
        $this->insertIdentifierRow(id: 9502, tenantId: 92, productId: 9002, value: 'VAL-001', technology: 'barcode_1d', format: 'code128');

        /** @var ProductIdentifierRepositoryInterface $repository */
        $repository = app(ProductIdentifierRepositoryInterface::class);

        $found = $repository->findByTenantAndValue(91, 'VAL-001');

        $this->assertInstanceOf(ProductIdentifier::class, $found);
        $this->assertSame(9501, $found->getId());
        $this->assertSame(91, $found->getTenantId());
    }

    public function test_paginate_and_where_return_mapped_domain_entities(): void
    {
        $this->insertIdentifierRow(id: 9601, tenantId: 91, productId: 9001, value: 'VAL-C', technology: 'barcode_1d', format: 'code128');
        $this->insertIdentifierRow(id: 9602, tenantId: 91, productId: 9001, value: 'VAL-A', technology: 'barcode_1d', format: 'code128');
        $this->insertIdentifierRow(id: 9603, tenantId: 92, productId: 9002, value: 'VAL-X', technology: 'barcode_1d', format: 'code128');

        /** @var ProductIdentifierRepositoryInterface $repository */
        $repository = app(ProductIdentifierRepositoryInterface::class);

        $paginator = $repository
            ->resetCriteria()
            ->where('tenant_id', 91)
            ->orderBy('value', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $items = $paginator->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(ProductIdentifier::class, $items[0]);
        $this->assertSame('VAL-A', $items[0]->getValue());
        $this->assertSame('VAL-C', $items[1]->getValue());

        $collection = $repository
            ->resetCriteria()
            ->where('tenant_id', 91)
            ->get();

        $this->assertCount(2, $collection);
        $this->assertContainsOnlyInstancesOf(ProductIdentifier::class, $collection);
    }

    private function insertIdentifierRow(
        int $id,
        int $tenantId,
        int $productId,
        string $value,
        string $technology,
        ?string $format
    ): void {
        DB::table('product_identifiers')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'variant_id' => null,
            'batch_id' => null,
            'serial_id' => null,
            'technology' => $technology,
            'format' => $format,
            'value' => $value,
            'gs1_company_prefix' => null,
            'gs1_application_identifiers' => null,
            'is_primary' => false,
            'is_active' => true,
            'format_config' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedReferenceData(): void
    {
        $this->insertTenantUomProductVariant(91, 9101, 9001, 9101, 'Product I1', 'product-i1', 'PI-001', 'V-001');
        $this->insertTenantUomProductVariant(92, 9201, 9002, 9201, 'Product I2', 'product-i2', 'PI-002', 'V-002');
    }

    private function insertTenantUomProductVariant(
        int $tenantId,
        int $uomId,
        int $productId,
        int $variantId,
        string $productName,
        string $productSlug,
        string $productSku,
        string $variantSku
    ): void {
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
            'id' => $uomId,
            'tenant_id' => $tenantId,
            'name' => 'Each',
            'symbol' => 'EA'.$tenantId,
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'id' => $productId,
            'tenant_id' => $tenantId,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => $productName,
            'slug' => $productSlug,
            'sku' => $productSku,
            'description' => null,
            'base_uom_id' => $uomId,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'uom_conversion_factor' => '1.0000000000',
            'is_batch_tracked' => false,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => null,
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

        DB::table('product_variants')->insert([
            'id' => $variantId,
            'product_id' => $productId,
            'sku' => $variantSku,
            'name' => 'Variant '.$variantSku,
            'is_default' => true,
            'is_active' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureAccountsTableExists(): void
    {
        if (Schema::hasTable('accounts')) {
            return;
        }

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
    }

    private function ensureBatchesAndSerialsTablesExist(): void
    {
        if (! Schema::hasTable('batches')) {
            Schema::create('batches', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('serials')) {
            Schema::create('serials', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
            });
        }
    }
}
