<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Product\Application\Contracts\UomConversionResolverServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Tests\TestCase;

class ProductUomConversionConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAccountsTableExists();
        $this->seedTenants();
        $this->seedUnitsOfMeasure();
    }

    public function test_product_purchase_and_sales_uoms_can_be_resolved_through_conversion_pairs(): void
    {
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = app(ProductRepositoryInterface::class);

        /** @var UomConversionRepositoryInterface $uomConversionRepository */
        $uomConversionRepository = app(UomConversionRepositoryInterface::class);

        DB::table('uom_conversions')->insert([
            'id' => 7001,
            'from_uom_id' => 2101,
            'to_uom_id' => 2102,
            'factor' => '12.0000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('uom_conversions')->insert([
            'id' => 7002,
            'from_uom_id' => 2101,
            'to_uom_id' => 2103,
            'factor' => '6.0000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = $productRepository->save(new Product(
            tenantId: 21,
            type: 'physical',
            name: 'Paint Bucket',
            slug: 'paint-bucket',
            sku: 'PB-001',
            baseUomId: 2101,
            purchaseUomId: 2102,
            salesUomId: 2103,
            uomConversionFactor: '1.000000',
        ));

        $this->assertNotNull($product->getId());

        $purchaseConversion = $uomConversionRepository->findByUomPair($product->getBaseUomId(), (int) $product->getPurchaseUomId());
        $salesConversion = $uomConversionRepository->findByUomPair($product->getBaseUomId(), (int) $product->getSalesUomId());

        $this->assertNotNull($purchaseConversion);
        $this->assertSame('12.0000000000', $purchaseConversion->getFactor());

        $this->assertNotNull($salesConversion);
        $this->assertSame('6.0000000000', $salesConversion->getFactor());
    }

    public function test_product_uom_pairs_without_conversion_are_detectable(): void
    {
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = app(ProductRepositoryInterface::class);

        /** @var UomConversionRepositoryInterface $uomConversionRepository */
        $uomConversionRepository = app(UomConversionRepositoryInterface::class);

        $product = $productRepository->save(new Product(
            tenantId: 21,
            type: 'physical',
            name: 'Wire Roll',
            slug: 'wire-roll',
            sku: 'WR-001',
            baseUomId: 2101,
            purchaseUomId: 2102,
            salesUomId: 2103,
            uomConversionFactor: '1.000000',
        ));

        $this->assertNotNull($product->getId());

        $purchaseConversion = $uomConversionRepository->findByUomPair($product->getBaseUomId(), (int) $product->getPurchaseUomId());
        $salesConversion = $uomConversionRepository->findByUomPair($product->getBaseUomId(), (int) $product->getSalesUomId());

        $this->assertNull($purchaseConversion);
        $this->assertNull($salesConversion);
    }

    public function test_resolver_supports_multi_hop_and_bidirectional_conversion_without_redundant_rows(): void
    {
        /** @var UomConversionResolverServiceInterface $resolver */
        $resolver = app(UomConversionResolverServiceInterface::class);

        DB::table('products')->insert([
            'id' => 2999,
            'tenant_id' => 21,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Multi Hop Product',
            'slug' => 'multi-hop-product',
            'sku' => 'MHP-001',
            'description' => null,
            'base_uom_id' => 2101,
            'purchase_uom_id' => 2102,
            'sales_uom_id' => 2103,
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

        DB::table('uom_conversions')->insert([
            [
                'tenant_id' => 21,
                'product_id' => 2999,
                'from_uom_id' => 2103,
                'to_uom_id' => 2102,
                'factor' => '2.0000000000',
                'is_bidirectional' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 21,
                'product_id' => 2999,
                'from_uom_id' => 2102,
                'to_uom_id' => 2101,
                'factor' => '6.0000000000',
                'is_bidirectional' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $forward = $resolver->convertQuantity(21, 2999, 2103, 2101, '3', 6);
        $reverse = $resolver->convertQuantity(21, 2999, 2101, 2103, '36', 6);

        $this->assertSame(0, bccomp($forward['quantity'], '36.000000', 6));
        $this->assertSame([2103, 2102, 2101], $forward['path']);

        $this->assertSame(0, bccomp($reverse['quantity'], '3.000000', 6));
        $this->assertSame([2101, 2102, 2103], $reverse['path']);
    }

    private function seedUnitsOfMeasure(): void
    {
        foreach ([
            [2101, 21, 'Case', 'CS'],
            [2102, 21, 'Piece', 'PC'],
            [2103, 21, 'Pack', 'PK'],
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
        DB::table('tenants')->insert([
            'id' => 21,
            'name' => 'Tenant 21',
            'slug' => 'tenant-21',
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
