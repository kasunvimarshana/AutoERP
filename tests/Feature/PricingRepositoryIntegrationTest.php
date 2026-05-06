<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Pricing\Domain\Entities\CustomerPriceList;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\Entities\SupplierPriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\CustomerPriceListRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\SupplierPriceListRepositoryInterface;
use Tests\TestCase;

class PricingRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;
    private int $tenant2Id = 2;
    private int $currencyId = 1;
    private int $productId = 1;
    private int $product2Id = 2;
    private int $uomId = 1;
    private int $customerId = 1;
    private int $supplierId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    // ── PriceListRepository ───────────────────────────────────────────────────

    public function test_price_list_save_and_find(): void
    {
        /** @var PriceListRepositoryInterface $repository */
        $repository = app(PriceListRepositoryInterface::class);

        $saved = $repository->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Retail Sales',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: true,
            isActive: true,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Retail Sales', $found->getName());
        $this->assertSame('sales', $found->getType());
        $this->assertSame($this->currencyId, $found->getCurrencyId());
        $this->assertTrue($found->isDefault());
        $this->assertTrue($found->isActive());
    }

    public function test_price_list_find_by_tenant_and_name(): void
    {
        /** @var PriceListRepositoryInterface $repository */
        $repository = app(PriceListRepositoryInterface::class);

        $saved = $repository->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Wholesale',
            type: 'sales',
            currencyId: $this->currencyId,
        ));

        $found = $repository->findByTenantAndName($this->tenantId, 'Wholesale');
        $wrongTenant = $repository->findByTenantAndName($this->tenant2Id, 'Wholesale');
        $notFound = $repository->findByTenantAndName($this->tenantId, 'NonExistent');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertNull($wrongTenant);
        $this->assertNull($notFound);
    }

    public function test_price_list_clear_default_by_type(): void
    {
        /** @var PriceListRepositoryInterface $repository */
        $repository = app(PriceListRepositoryInterface::class);

        $pl1 = $repository->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Default Sales',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: true,
        ));
        $pl2 = $repository->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Secondary Sales',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: true,
        ));
        // purchase list should be unaffected
        $pl3 = $repository->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Default Purchase',
            type: 'purchase',
            currencyId: $this->currencyId,
            isDefault: true,
        ));

        // Clear default for 'sales', excluding pl2 (the new default)
        $repository->clearDefaultByType($this->tenantId, 'sales', $pl2->getId());

        $refetched1 = $repository->find($pl1->getId());
        $refetched2 = $repository->find($pl2->getId());
        $refetched3 = $repository->find($pl3->getId());

        $this->assertFalse($refetched1->isDefault());   // cleared
        $this->assertTrue($refetched2->isDefault());    // excluded → still default
        $this->assertTrue($refetched3->isDefault());    // purchase → untouched
    }

    public function test_price_list_clear_default_by_type_without_exclude(): void
    {
        /** @var PriceListRepositoryInterface $repository */
        $repository = app(PriceListRepositoryInterface::class);

        $pl1 = $repository->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Sales A',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: true,
        ));
        $pl2 = $repository->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Sales B',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: true,
        ));

        $repository->clearDefaultByType($this->tenantId, 'sales');

        $this->assertFalse($repository->find($pl1->getId())->isDefault());
        $this->assertFalse($repository->find($pl2->getId())->isDefault());
    }

    // ── PriceListItemRepository ───────────────────────────────────────────────

    public function test_price_list_item_save_and_find(): void
    {
        /** @var PriceListRepositoryInterface $plRepo */
        $plRepo = app(PriceListRepositoryInterface::class);

        /** @var PriceListItemRepositoryInterface $repository */
        $repository = app(PriceListItemRepositoryInterface::class);

        $pl = $plRepo->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Item Test List',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: true,
        ));

        $saved = $repository->save(new PriceListItem(
            tenantId: $this->tenantId,
            priceListId: $pl->getId(),
            productId: $this->productId,
            uomId: $this->uomId,
            price: '25.000000',
            minQuantity: '1.000000',
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame($this->productId, $found->getProductId());
        $this->assertSame($pl->getId(), $found->getPriceListId());
        $this->assertSame('25.000000', $found->getPrice());
    }

    public function test_find_best_match_returns_default_list_price(): void
    {
        /** @var PriceListRepositoryInterface $plRepo */
        $plRepo = app(PriceListRepositoryInterface::class);

        /** @var PriceListItemRepositoryInterface $repository */
        $repository = app(PriceListItemRepositoryInterface::class);

        $pl = $plRepo->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Default Price List',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: true,
            isActive: true,
        ));

        $repository->save(new PriceListItem(
            tenantId: $this->tenantId,
            priceListId: $pl->getId(),
            productId: $this->productId,
            uomId: $this->uomId,
            price: '19.990000',
            minQuantity: '1.000000',
        ));

        $match = $repository->findBestMatch(
            tenantId: $this->tenantId,
            type: 'sales',
            productId: $this->productId,
            variantId: null,
            uomId: $this->uomId,
            quantity: '5.000000',
            currencyId: $this->currencyId,
            customerId: null,
            supplierId: null,
            priceDate: new \DateTimeImmutable('2025-06-01'),
        );

        $this->assertNotNull($match);
        $this->assertSame('19.990000', number_format((float) $match['price'], 6, '.', ''));
    }

    public function test_find_best_match_prefers_higher_min_quantity_tier(): void
    {
        /** @var PriceListRepositoryInterface $plRepo */
        $plRepo = app(PriceListRepositoryInterface::class);

        /** @var PriceListItemRepositoryInterface $repository */
        $repository = app(PriceListItemRepositoryInterface::class);

        $pl = $plRepo->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Tiered List',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: true,
            isActive: true,
        ));

        // Two tiers: qty ≥ 1 @ 20.00, qty ≥ 10 @ 15.00
        $repository->save(new PriceListItem(
            tenantId: $this->tenantId,
            priceListId: $pl->getId(),
            productId: $this->productId,
            uomId: $this->uomId,
            price: '20.000000',
            minQuantity: '1.000000',
        ));
        $repository->save(new PriceListItem(
            tenantId: $this->tenantId,
            priceListId: $pl->getId(),
            productId: $this->productId,
            uomId: $this->uomId,
            price: '15.000000',
            minQuantity: '10.000000',
        ));

        // Order qty = 10 → should pick the higher min-qty tier (15.00)
        $match = $repository->findBestMatch(
            tenantId: $this->tenantId,
            type: 'sales',
            productId: $this->productId,
            variantId: null,
            uomId: $this->uomId,
            quantity: '10.000000',
            currencyId: $this->currencyId,
            customerId: null,
            supplierId: null,
            priceDate: new \DateTimeImmutable('2025-06-01'),
        );

        $this->assertNotNull($match);
        $this->assertSame('15.000000', number_format((float) $match['price'], 6, '.', ''));
    }

    public function test_find_best_match_prefers_customer_assigned_list(): void
    {
        /** @var PriceListRepositoryInterface $plRepo */
        $plRepo = app(PriceListRepositoryInterface::class);

        /** @var PriceListItemRepositoryInterface $plItemRepo */
        $plItemRepo = app(PriceListItemRepositoryInterface::class);

        /** @var CustomerPriceListRepositoryInterface $cplRepo */
        $cplRepo = app(CustomerPriceListRepositoryInterface::class);

        $defaultPl = $plRepo->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Default',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: true,
            isActive: true,
        ));

        $customerPl = $plRepo->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'VIP Customer',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: false,
            isActive: true,
        ));

        $plItemRepo->save(new PriceListItem(
            tenantId: $this->tenantId,
            priceListId: $defaultPl->getId(),
            productId: $this->productId,
            uomId: $this->uomId,
            price: '30.000000',
            minQuantity: '1.000000',
        ));

        $plItemRepo->save(new PriceListItem(
            tenantId: $this->tenantId,
            priceListId: $customerPl->getId(),
            productId: $this->productId,
            uomId: $this->uomId,
            price: '22.000000',
            minQuantity: '1.000000',
        ));

        // Assign customer-specific list
        $cplRepo->save(new CustomerPriceList(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            priceListId: $customerPl->getId(),
            priority: 10,
        ));

        $match = $plItemRepo->findBestMatch(
            tenantId: $this->tenantId,
            type: 'sales',
            productId: $this->productId,
            variantId: null,
            uomId: $this->uomId,
            quantity: '1.000000',
            currencyId: $this->currencyId,
            customerId: $this->customerId,
            supplierId: null,
            priceDate: new \DateTimeImmutable('2025-06-01'),
        );

        $this->assertNotNull($match);
        // Must return customer-specific price, not the default
        $this->assertSame('22.000000', number_format((float) $match['price'], 6, '.', ''));
    }

    public function test_find_best_match_returns_null_for_inactive_list(): void
    {
        /** @var PriceListRepositoryInterface $plRepo */
        $plRepo = app(PriceListRepositoryInterface::class);

        /** @var PriceListItemRepositoryInterface $repository */
        $repository = app(PriceListItemRepositoryInterface::class);

        $pl = $plRepo->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Inactive List',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: true,
            isActive: false,       // inactive
        ));

        $repository->save(new PriceListItem(
            tenantId: $this->tenantId,
            priceListId: $pl->getId(),
            productId: $this->productId,
            uomId: $this->uomId,
            price: '9.990000',
            minQuantity: '1.000000',
        ));

        $match = $repository->findBestMatch(
            tenantId: $this->tenantId,
            type: 'sales',
            productId: $this->productId,
            variantId: null,
            uomId: $this->uomId,
            quantity: '1.000000',
            currencyId: $this->currencyId,
            customerId: null,
            supplierId: null,
            priceDate: new \DateTimeImmutable('2025-06-01'),
        );

        $this->assertNull($match);
    }

    public function test_find_best_match_respects_price_list_validity_dates(): void
    {
        /** @var PriceListRepositoryInterface $plRepo */
        $plRepo = app(PriceListRepositoryInterface::class);

        /** @var PriceListItemRepositoryInterface $repository */
        $repository = app(PriceListItemRepositoryInterface::class);

        $pl = $plRepo->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Jan Only List',
            type: 'sales',
            currencyId: $this->currencyId,
            isDefault: true,
            isActive: true,
            validFrom: new \DateTimeImmutable('2025-01-01'),
            validTo: new \DateTimeImmutable('2025-01-31'),
        ));

        $repository->save(new PriceListItem(
            tenantId: $this->tenantId,
            priceListId: $pl->getId(),
            productId: $this->productId,
            uomId: $this->uomId,
            price: '50.000000',
            minQuantity: '1.000000',
        ));

        // Within validity → found
        $inRange = $repository->findBestMatch(
            tenantId: $this->tenantId,
            type: 'sales',
            productId: $this->productId,
            variantId: null,
            uomId: $this->uomId,
            quantity: '1.000000',
            currencyId: $this->currencyId,
            customerId: null,
            supplierId: null,
            priceDate: new \DateTimeImmutable('2025-01-15'),
        );

        // Outside validity → null
        $outOfRange = $repository->findBestMatch(
            tenantId: $this->tenantId,
            type: 'sales',
            productId: $this->productId,
            variantId: null,
            uomId: $this->uomId,
            quantity: '1.000000',
            currencyId: $this->currencyId,
            customerId: null,
            supplierId: null,
            priceDate: new \DateTimeImmutable('2025-06-01'),
        );

        $this->assertNotNull($inRange);
        $this->assertNull($outOfRange);
    }

    // ── CustomerPriceListRepository ───────────────────────────────────────────

    public function test_customer_price_list_save_and_find(): void
    {
        /** @var PriceListRepositoryInterface $plRepo */
        $plRepo = app(PriceListRepositoryInterface::class);

        /** @var CustomerPriceListRepositoryInterface $repository */
        $repository = app(CustomerPriceListRepositoryInterface::class);

        $pl = $plRepo->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Customer VIP',
            type: 'sales',
            currencyId: $this->currencyId,
        ));

        $saved = $repository->save(new CustomerPriceList(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            priceListId: $pl->getId(),
            priority: 5,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame($this->customerId, $found->getCustomerId());
        $this->assertSame($pl->getId(), $found->getPriceListId());
        $this->assertSame(5, $found->getPriority());
    }

    // ── SupplierPriceListRepository ───────────────────────────────────────────

    public function test_supplier_price_list_save_and_find(): void
    {
        /** @var PriceListRepositoryInterface $plRepo */
        $plRepo = app(PriceListRepositoryInterface::class);

        /** @var SupplierPriceListRepositoryInterface $repository */
        $repository = app(SupplierPriceListRepositoryInterface::class);

        $pl = $plRepo->save(new PriceList(
            tenantId: $this->tenantId,
            name: 'Supplier Purchase',
            type: 'purchase',
            currencyId: $this->currencyId,
        ));

        $saved = $repository->save(new SupplierPriceList(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            priceListId: $pl->getId(),
            priority: 3,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame($this->supplierId, $found->getSupplierId());
        $this->assertSame($pl->getId(), $found->getPriceListId());
        $this->assertSame(3, $found->getPriority());
    }

    // ── Seed ──────────────────────────────────────────────────────────────────

    private function seedReferenceData(): void
    {
        foreach ([$this->tenantId, $this->tenant2Id] as $tid) {
            DB::table('tenants')->insert([
                'id' => $tid,
                'name' => 'Tenant '.$tid,
                'slug' => 'tenant-'.$tid,
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

        DB::table('currencies')->insert([
            'id' => $this->currencyId,
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('units_of_measure')->insert([
            'id' => $this->uomId,
            'tenant_id' => $this->tenantId,
            'name' => 'Each',
            'symbol' => 'EA',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            ['id' => $this->productId,  'tenant_id' => $this->tenantId, 'category_id' => null, 'brand_id' => null, 'org_unit_id' => null, 'type' => 'physical', 'name' => 'Product A', 'slug' => 'product-a', 'sku' => 'SKU-A', 'description' => null, 'image_path' => null, 'base_uom_id' => $this->uomId, 'purchase_uom_id' => null, 'sales_uom_id' => null, 'tax_group_id' => null, 'uom_conversion_factor' => 1, 'is_batch_tracked' => false, 'is_lot_tracked' => false, 'is_serial_tracked' => false, 'valuation_method' => 'fifo', 'standard_cost' => null, 'income_account_id' => null, 'cogs_account_id' => null, 'inventory_account_id' => null, 'expense_account_id' => null, 'is_active' => true, 'metadata' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => $this->product2Id, 'tenant_id' => $this->tenantId, 'category_id' => null, 'brand_id' => null, 'org_unit_id' => null, 'type' => 'physical', 'name' => 'Product B', 'slug' => 'product-b', 'sku' => 'SKU-B', 'description' => null, 'image_path' => null, 'base_uom_id' => $this->uomId, 'purchase_uom_id' => null, 'sales_uom_id' => null, 'tax_group_id' => null, 'uom_conversion_factor' => 1, 'is_batch_tracked' => false, 'is_lot_tracked' => false, 'is_serial_tracked' => false, 'valuation_method' => 'fifo', 'standard_cost' => null, 'income_account_id' => null, 'cogs_account_id' => null, 'inventory_account_id' => null, 'expense_account_id' => null, 'is_active' => true, 'metadata' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('customers')->insert([
            'id' => $this->customerId,
            'tenant_id' => $this->tenantId,
            'user_id' => null,
            'org_unit_id' => null,
            'customer_code' => 'CUST-001',
            'name' => 'Test Customer',
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => $this->currencyId,
            'credit_limit' => 0,
            'payment_terms_days' => 30,
            'ar_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('suppliers')->insert([
            'id' => $this->supplierId,
            'tenant_id' => $this->tenantId,
            'user_id' => null,
            'org_unit_id' => null,
            'supplier_code' => 'SUP-001',
            'name' => 'Test Supplier',
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => null,
            'payment_terms_days' => 30,
            'ap_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
