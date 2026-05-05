<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Product\Domain\Entities\UomConversion;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Tests\TestCase;

class UomConversionRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenants();
        $this->seedUnitsOfMeasure();
        $this->seedProducts();
    }

    public function test_save_creates_and_updates_uom_conversion(): void
    {
        /** @var UomConversionRepositoryInterface $repository */
        $repository = app(UomConversionRepositoryInterface::class);

        $created = $repository->save(new UomConversion(
            fromUomId: 1101,
            toUomId: 1102,
            factor: '12.5000000000',
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('12.5000000000', $created->getFactor());

        $updated = $repository->save(new UomConversion(
            id: $created->getId(),
            fromUomId: 1101,
            toUomId: 1102,
            factor: '13.5000000000',
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('13.5000000000', $updated->getFactor());
    }

    public function test_find_by_uom_pair_returns_domain_entity(): void
    {
        $this->insertConversionRow(id: 501, fromUomId: 1101, toUomId: 1102, factor: '1000.0000000000');

        /** @var UomConversionRepositoryInterface $repository */
        $repository = app(UomConversionRepositoryInterface::class);

        $found = $repository->findByUomPair(1101, 1102);

        $this->assertInstanceOf(UomConversion::class, $found);
        $this->assertSame(501, $found->getId());
        $this->assertSame('1000.0000000000', $found->getFactor());
    }

    public function test_paginate_and_where_return_mapped_domain_entities(): void
    {
        $this->insertConversionRow(id: 601, fromUomId: 1101, toUomId: 1102, factor: '1000.0000000000');
        $this->insertConversionRow(id: 602, fromUomId: 1101, toUomId: 1103, factor: '2.5000000000');
        $this->insertConversionRow(id: 603, fromUomId: 1201, toUomId: 1202, factor: '3.0000000000');

        /** @var UomConversionRepositoryInterface $repository */
        $repository = app(UomConversionRepositoryInterface::class);

        $paginator = $repository
            ->resetCriteria()
            ->where('from_uom_id', 1101)
            ->orderBy('id', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $items = $paginator->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(UomConversion::class, $items[0]);
        $this->assertSame(601, $items[0]->getId());
        $this->assertSame(602, $items[1]->getId());

        $collection = $repository
            ->resetCriteria()
            ->where('from_uom_id', 1101)
            ->get();

        $this->assertCount(2, $collection);
        $this->assertContainsOnlyInstancesOf(UomConversion::class, $collection);
    }

    public function test_list_for_resolution_returns_scope_and_global_rows(): void
    {
        $this->insertConversionRow(id: 701, fromUomId: 1101, toUomId: 1102, factor: '2.0000000000', tenantId: 11, productId: null);
        $this->insertConversionRow(id: 702, fromUomId: 1102, toUomId: 1103, factor: '3.0000000000', tenantId: 11, productId: 9001);
        $this->insertConversionRow(id: 703, fromUomId: 1201, toUomId: 1202, factor: '4.0000000000', tenantId: 12, productId: null);

        /** @var UomConversionRepositoryInterface $repository */
        $repository = app(UomConversionRepositoryInterface::class);

        $rows = $repository->listForResolution(11, 9001);

        $this->assertCount(2, $rows);
        $this->assertSame(701, $rows[0]->getId());
        $this->assertSame(702, $rows[1]->getId());
    }

    private function insertConversionRow(int $id, int $fromUomId, int $toUomId, string $factor, ?int $tenantId = null, ?int $productId = null): void
    {
        DB::table('uom_conversions')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'from_uom_id' => $fromUomId,
            'to_uom_id' => $toUomId,
            'factor' => $factor,
            'is_bidirectional' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedUnitsOfMeasure(): void
    {
        foreach ([
            [1101, 11, 'Kilogram', 'KG'],
            [1102, 11, 'Gram', 'G'],
            [1103, 11, 'Pound', 'LB'],
            [1201, 12, 'Liter', 'L'],
            [1202, 12, 'Milliliter', 'ML'],
        ] as [$id, $tenantId, $name, $symbol]) {
            DB::table('units_of_measure')->insert([
                'id' => $id,
                'tenant_id' => $tenantId,
                'name' => $name,
                'symbol' => $symbol,
                'type' => 'unit',
                'is_base' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedTenants(): void
    {
        foreach ([11, 12] as $tenantId) {
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
    }

    private function seedProducts(): void
    {
        DB::table('products')->insert([
            'id' => 9001,
            'tenant_id' => 11,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Repository Seed Product',
            'slug' => 'repository-seed-product',
            'sku' => 'RSP-9001',
            'description' => null,
            'base_uom_id' => 1101,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
            'uom_conversion_factor' => '1.0000000000',
            'is_batch_tracked' => false,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => '1.000000',
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
}
