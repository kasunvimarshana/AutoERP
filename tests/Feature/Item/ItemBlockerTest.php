<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\Item;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\ItemQueryService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class ItemBlockerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_item_cannot_be_deleted_when_referenced_by_sale_lines(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Org Item '.$suffix,
                'code' => 'ORGI-'.$suffix,
            ]);

            $uomId = DB::table('unit_of_measures')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'PCS-'.$suffix,
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'is_active' => true,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $warehouseId = DB::table('warehouses')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'Warehouse '.$suffix,
                'code' => 'WH-'.$suffix,
                'type' => 'standard',
                'is_active' => true,
                'is_default' => true,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $item = Item::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'ITM-SALE-'.$suffix,
                'name' => 'Sale Test Item '.$suffix,
                'item_type' => 'stock',
                'tracking_type' => 'none',
                'costing_method' => 'fifo',
                'base_uom_id' => $uomId,
                'is_stockable' => true,
                'is_combo' => false,
                'is_active' => true,
            ]);

            $customer = DB::table('customers')->insertGetId([
                'tenant_id' => $tenantId,
                'customer_number' => 'CN-'.$suffix,
                'customer_type' => 'individual',
                'name' => 'Sales Customer',
                'code' => 'CUS-'.$suffix,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $saleId = DB::table('sales')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'customer_id' => $customer,
                'warehouse_id' => $warehouseId,
                'sale_number' => 'SALE-'.$suffix,
                'sale_date' => '2026-11-22',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('sale_lines')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'sale_id' => $saleId,
                'line_number' => 1,
                'item_id' => $item->getKey(),
                'uom_id' => $uomId,
                'quantity' => 1,
                'base_quantity' => 1,
                'unit_price' => 100,
                'line_total' => 100,
                'description' => 'Test Line',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Item cannot be deleted while related setup or transaction records reference it.');

            app(ItemQueryService::class)->delete($item);
        });
    }

    public function test_item_cannot_be_deleted_when_referenced_by_promotion_card_benefits(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Org Promo '.$suffix,
                'code' => 'ORGP-'.$suffix,
            ]);

            $uomId = DB::table('unit_of_measures')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'PCS-P-'.$suffix,
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'is_active' => true,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $item = Item::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'ITM-PROMO-'.$suffix,
                'name' => 'Promo Item '.$suffix,
                'item_type' => 'stock',
                'tracking_type' => 'none',
                'costing_method' => 'fifo',
                'base_uom_id' => $uomId,
                'is_stockable' => true,
                'is_combo' => false,
                'is_active' => true,
            ]);

            $templateId = DB::table('promotion_card_templates')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => 'Card Template '.$suffix,
                'code' => 'TMPL-'.$suffix,
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('promotion_card_benefits')->insert([
                'tenant_id' => $tenantId,
                'template_id' => $templateId,
                'position' => 1,
                'kind' => 'item_discount',
                'item_id' => $item->getKey(),
                'discount_type' => 'percentage',
                'discount_rate' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Item cannot be deleted while related setup or transaction records reference it.');

            app(ItemQueryService::class)->delete($item);
        });
    }

    public function test_unreferenced_item_can_be_deleted(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Org Unref '.$suffix,
                'code' => 'ORGU-'.$suffix,
            ]);

            $uomId = DB::table('unit_of_measures')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'PCS-U-'.$suffix,
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'is_active' => true,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $item = Item::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'ITM-UNREF-'.$suffix,
                'name' => 'Unref Item '.$suffix,
                'item_type' => 'stock',
                'tracking_type' => 'none',
                'costing_method' => 'fifo',
                'base_uom_id' => $uomId,
                'is_stockable' => true,
                'is_combo' => false,
                'is_active' => true,
            ]);

            app(ItemQueryService::class)->delete($item);

            $this->assertSoftDeleted('items', ['id' => $item->getKey()]);
        });
    }

    private function tenant(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Item Test Tenant',
            'code' => 'itm-test-'.$suffix,
            'slug' => 'item-test-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
