<?php

declare(strict_types=1);

namespace Modules\Selling\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Services\StockMovementService;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoicePrintService;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\DTOs\ItemPriceData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Modules\Item\Services\ItemPriceService;
use Modules\Selling\Services\SalePostingService;
use Modules\Selling\Services\SaleReturnPostingService;
use Modules\Tenant\Models\TenantModel;
use Tests\Support\CurrencyFixture;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class SellingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_posts_invoice_and_stock_atomically_and_return_records_credit_note(): void
    {
        $context = $this->context();
        FinancePostingFixture::seedCustomerInvoiceProfiles($context['tenant_id']);

        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context): void {
            app(StockMovementService::class)->record(new StockMovementData(
                tenantId: $context['tenant_id'],
                movementDate: '2026-09-26',
                movementType: InventoryMovementType::Receipt,
                direction: InventoryDirection::In,
                itemId: $context['item_id'],
                warehouseId: $context['warehouse_id'],
                quantity: '5.000000',
                unitCost: '20.000000',
                uomId: $context['uom_id'],
            ));

            $salePayload = [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => null,
                'customer_id' => $context['customer_id'],
                'warehouse_id' => $context['warehouse_id'],
                'sale_date' => '2026-09-26',
                'lines' => [[
                    'item_id' => $context['item_id'],
                    'uom_id' => $context['uom_id'],
                    'quantity' => '2.000000',
                ]],
            ];
            $saleKey = 'selling-test-sale-'.Str::uuid();
            $sale = app(SalePostingService::class)->create($salePayload, $saleKey);
            $duplicateSale = app(SalePostingService::class)->create($salePayload, $saleKey);

            $this->assertSame($sale->getKey(), $duplicateSale->getKey());
            $this->assertSame('posted', $sale->status);
            $this->assertSame(InvoiceStatus::Posted, $sale->invoice->status);
            $this->assertSame('200.000000', (string) $sale->invoice->grand_total);
            $this->assertTrue(app(InvoicePrintService::class)->viewData($sale->invoice)['document']['uses_focused_print']);
            $this->assertSame(1, InventoryMovement::query()->where('source_type', 'sale')->count());
            $this->assertSame('3.000000', (string) InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);

            try {
                app(SalePostingService::class)->create(array_replace($salePayload, [
                    'lines' => [[
                        'item_id' => $context['item_id'],
                        'uom_id' => $context['uom_id'],
                        'quantity' => '10.000000',
                    ]],
                ]), 'selling-test-insufficient-stock-'.Str::uuid());
                $this->fail('Insufficient stock must prevent sale posting.');
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('available stock', $exception->getMessage());
            }
            $this->assertSame(1, Invoice::query()->count());
            $this->assertSame(2, InventoryMovement::query()->count());
            $this->assertSame('3.000000', (string) InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);

            $returnPayload = [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => null,
                'sale_id' => (int) $sale->getKey(),
                'expected_version' => (int) $sale->row_version,
                'return_date' => '2026-09-26',
                'reason' => 'Customer returned one item',
                'lines' => [[
                    'sale_line_id' => (int) $sale->lines->first()->getKey(),
                    'quantity' => '1.000000',
                ]],
            ];
            $returnKey = 'selling-test-return-'.Str::uuid();
            $sale = app(SaleReturnPostingService::class)->create($returnPayload, $returnKey);
            $duplicateReturn = app(SaleReturnPostingService::class)->create($returnPayload, $returnKey);

            $this->assertSame($sale->getKey(), $duplicateReturn->getKey());
            $this->assertSame(1, $sale->returns->count());
            $this->assertSame('CRN-', substr((string) $sale->returns->first()->return_number, 0, 4));
            $this->assertSame('100.000000', (string) $sale->returns->first()->credit_amount);
            $this->assertSame('100.000000', (string) $sale->invoice->balance->credit_allocated_amount);
            $this->assertSame('4.000000', (string) InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);
        });
    }

    /** @return array<string, int> */
    private function context(): array
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-SELL-'.$suffix,
            'name' => 'Selling '.$suffix,
            'slug' => 'selling-'.$suffix,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $currencyId = CurrencyFixture::create();
        TenantModel::query()->whereKey($tenantId)->update(['base_currency_id' => $currencyId]);
        $uomId = (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'code' => 'PCS-'.$suffix,
            'name' => 'Pieces',
            'symbol' => 'pcs',
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $warehouseId = (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => 'Sales warehouse '.$suffix,
            'code' => 'WH-'.$suffix,
            'type' => 'standard',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customerId = (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUS-'.$suffix,
            'code' => 'CUS-'.$suffix,
            'name' => 'Walk-in '.$suffix,
            'display_name' => 'Walk-in '.$suffix,
            'customer_type' => 'retail',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $item = $this->withTenantExecutionContext($tenantId, fn (): Item => app(ItemCreationService::class)->create(new CreateItemData(
            tenantId: $tenantId,
            code: 'ITEM-'.$suffix,
            name: 'Sales item '.$suffix,
            itemType: ItemType::Stock,
            trackingType: TrackingType::None,
            costingMethod: CostingMethod::Fifo,
            baseUomId: $uomId,
            isStockable: true,
        )));
        $this->withTenantExecutionContext($tenantId, fn () => app(ItemPriceService::class)->create($item, new ItemPriceData(
            priceType: ItemPriceType::Sales,
            amount: '100.000000',
            currencyId: $currencyId,
            uomId: $uomId,
            organizationUnitId: null,
            effectiveFrom: '2026-01-01',
        )));

        return [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'warehouse_id' => $warehouseId,
            'uom_id' => $uomId,
            'item_id' => (int) $item->getKey(),
        ];
    }
}
