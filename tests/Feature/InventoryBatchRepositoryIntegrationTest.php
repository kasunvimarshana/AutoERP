<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Domain\Entities\Batch;
use Modules\Inventory\Domain\RepositoryInterfaces\BatchRepositoryInterface;
use Tests\TestCase;

class InventoryBatchRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAccountsTableExists();
        $this->seedReferenceData();
    }

    public function test_save_creates_and_updates_batch(): void
    {
        /** @var BatchRepositoryInterface $repository */
        $repository = app(BatchRepositoryInterface::class);

        $created = $repository->save(new Batch(
            tenantId: 71,
            productId: 7101,
            variantId: null,
            batchNumber: 'BATCH-001',
            lotNumber: 'LOT-001',
            manufactureDate: '2026-01-10',
            expiryDate: '2027-01-10',
            receivedDate: '2026-01-11',
            supplierId: null,
            status: 'active',
            notes: 'First receipt',
            metadata: ['source' => 'integration-test'],
            salesPrice: '12.500000',
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('BATCH-001', $created->getBatchNumber());

        $created->update(
            variantId: null,
            batchNumber: 'BATCH-001-REV',
            lotNumber: 'LOT-001-REV',
            manufactureDate: '2026-01-10',
            expiryDate: '2027-06-10',
            receivedDate: '2026-01-11',
            supplierId: null,
            status: 'quarantine',
            notes: 'Updated after quality check',
            metadata: ['source' => 'integration-test', 'updated' => true],
            salesPrice: '13.000000',
        );

        $updated = $repository->save($created);

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('BATCH-001-REV', $updated->getBatchNumber());
        $this->assertSame('quarantine', $updated->getStatus());

        $row = DB::table('batches')->where('id', $updated->getId())->first();
        $this->assertNotNull($row);
        $this->assertSame('BATCH-001-REV', (string) $row->batch_number);
        $this->assertSame(0, bccomp((string) $row->sales_price, '13.000000', 6));
    }

    public function test_find_and_delete_behave_as_expected(): void
    {
        $this->insertBatchRow(
            id: 8101,
            tenantId: 81,
            productId: 8101,
            variantId: null,
            batchNumber: 'BATCH-FIND-001',
            lotNumber: 'LOT-FIND-001',
            status: 'active',
        );

        /** @var BatchRepositoryInterface $repository */
        $repository = app(BatchRepositoryInterface::class);

        $found = $repository->find(8101);

        $this->assertInstanceOf(Batch::class, $found);
        $this->assertSame(8101, $found->getId());
        $this->assertSame(81, $found->getTenantId());
        $this->assertSame('BATCH-FIND-001', $found->getBatchNumber());

        $deleted = $repository->delete(8101);

        $this->assertTrue($deleted);
        $this->assertNull($repository->find(8101));
    }

    public function test_find_by_tenant_applies_filters_sort_and_pagination(): void
    {
        $this->insertBatchRow(
            id: 9101,
            tenantId: 91,
            productId: 9101,
            variantId: null,
            batchNumber: 'BATCH-C',
            lotNumber: 'LOT-C',
            status: 'active',
        );
        $this->insertBatchRow(
            id: 9102,
            tenantId: 91,
            productId: 9101,
            variantId: 91011,
            batchNumber: 'BATCH-A',
            lotNumber: 'LOT-A',
            status: 'quarantine',
        );
        $this->insertBatchRow(
            id: 9103,
            tenantId: 92,
            productId: 9201,
            variantId: null,
            batchNumber: 'BATCH-X',
            lotNumber: 'LOT-X',
            status: 'active',
        );

        /** @var BatchRepositoryInterface $repository */
        $repository = app(BatchRepositoryInterface::class);

        $filtered = $repository->findByTenant(
            tenantId: 91,
            filters: [
                'status' => 'quarantine',
                'batch_number' => 'BATCH',
                'lot_number' => 'LOT',
            ],
            perPage: 15,
            page: 1,
            sort: 'batch_number',
        );

        $items = $filtered->items();

        $this->assertCount(1, $items);
        $this->assertContainsOnlyInstancesOf(Batch::class, $items);
        $this->assertSame(9102, $items[0]->getId());
        $this->assertSame('BATCH-A', $items[0]->getBatchNumber());

        $sortedDesc = $repository->findByTenant(
            tenantId: 91,
            filters: [],
            perPage: 15,
            page: 1,
            sort: '-batch_number',
        );

        $descItems = $sortedDesc->items();

        $this->assertCount(2, $descItems);
        $this->assertSame('BATCH-C', $descItems[0]->getBatchNumber());
        $this->assertSame('BATCH-A', $descItems[1]->getBatchNumber());
    }

    private function seedReferenceData(): void
    {
        $this->insertTenantUomProduct(71, 701, 7101, 'Repo Batch Product 71', 'repo-batch-product-71', 'RB-71-01');
        $this->insertTenantUomProduct(81, 801, 8101, 'Repo Batch Product 81', 'repo-batch-product-81', 'RB-81-01');
        $this->insertTenantUomProduct(91, 901, 9101, 'Repo Batch Product 91', 'repo-batch-product-91', 'RB-91-01');
        $this->insertTenantUomProduct(92, 902, 9201, 'Repo Batch Product 92', 'repo-batch-product-92', 'RB-92-01');

        DB::table('product_variants')->insert([
            'id' => 91011,
            'product_id' => 9101,
            'sku' => 'RB-91-01-V1',
            'name' => 'Variant 1',
            'is_default' => true,
            'is_active' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertTenantUomProduct(
        int $tenantId,
        int $uomId,
        int $productId,
        string $productName,
        string $productSlug,
        string $productSku
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
            'deleted_at' => null,
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
            'tax_group_id' => null,
            'uom_conversion_factor' => '1.0000000000',
            'is_batch_tracked' => true,
            'is_lot_tracked' => true,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => null,
            'income_account_id' => null,
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'expense_account_id' => null,
            'is_active' => true,
            'image_path' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function insertBatchRow(
        int $id,
        int $tenantId,
        int $productId,
        ?int $variantId,
        string $batchNumber,
        ?string $lotNumber,
        string $status,
    ): void {
        DB::table('batches')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'batch_number' => $batchNumber,
            'lot_number' => $lotNumber,
            'manufacture_date' => null,
            'expiry_date' => null,
            'received_date' => null,
            'supplier_id' => null,
            'status' => $status,
            'notes' => null,
            'metadata' => null,
            'sales_price' => null,
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
}
