<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Enums\AdjustmentStatus;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Services\OpeningStockCsvImportService;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Item\Models\Item;

final class OpeningStockCsvImportTest extends InventoryTestCase
{
    public function test_it_previews_and_creates_one_draft_opening_balance_adjustment(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->configureBaseUom($tenantId, $item);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $file = $this->csv(
                "item_code,variant_code,uom_code,opening_quantity,unit_cost,batch_number,serial_number,reason\n"
                ."{$item->code},,,15,8.50,,,Initial stock\n",
            );
            $service = app(OpeningStockCsvImportService::class);

            $preview = $service->preview($file, $tenantId, null, $warehouseId, null);

            $this->assertTrue($preview->isValid());
            $this->assertSame(1, $preview->toArray()['valid_rows']);
            $this->assertSame('15.000000', $preview->rows[0]['base_quantity']);
            $this->assertSame('8.500000', $preview->rows[0]['base_unit_cost']);

            $adjustment = $service->createDraft(
                $file,
                $tenantId,
                null,
                '2026-07-21',
                $warehouseId,
                null,
                null,
            );

            $this->assertSame(AdjustmentType::OpeningBalance, $adjustment->adjustment_type);
            $this->assertSame(AdjustmentStatus::Draft, $adjustment->status);
            $this->assertCount(1, $adjustment->lines);
            $this->assertSame('0.000000', (string) $adjustment->lines[0]->system_quantity);
            $this->assertSame('15.000000', (string) $adjustment->lines[0]->counted_quantity);
            $this->assertSame('8.500000', (string) $adjustment->lines[0]->unit_cost);

            app(StockAdjustmentService::class)->post($adjustment);
            $availability = app(StockAvailabilityService::class)->availability(new StockBalanceData(
                $tenantId,
                (int) $item->getKey(),
                $warehouseId,
            ));
            $this->assertSame('15.000000', $availability->quantityOnHand);
        });
    }

    public function test_it_rejects_opening_stock_for_a_dimension_that_already_has_stock(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->configureBaseUom($tenantId, $item);
        $this->receipt($tenantId, $warehouseId, $item, '2.000000', '5.000000');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $file = $this->csv(
                "item_code,opening_quantity,unit_cost\n"
                ."{$item->code},3,6\n",
            );
            $service = app(OpeningStockCsvImportService::class);

            $preview = $service->preview($file, $tenantId, null, $warehouseId, null);

            $this->assertFalse($preview->isValid());
            $this->assertSame(1, $preview->toArray()['invalid_rows']);
            $this->assertStringContainsString('current stock', implode(' ', $preview->rows[0]['errors']));

            try {
                $service->createDraft($file, $tenantId, null, '2026-07-21', $warehouseId, null, null);
                $this->fail('Expected invalid opening stock CSV to be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey(OpeningStockCsvImportService::FILE_FIELD, $exception->errors());
            }

            $this->assertDatabaseCount('inventory_adjustments', 0);
        });
    }

    public function test_it_reports_duplicate_dimensions_and_rejects_unsupported_headers(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->configureBaseUom($tenantId, $item);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $service = app(OpeningStockCsvImportService::class);
            $duplicates = $this->csv(
                "item_code,opening_quantity,unit_cost\n"
                ."{$item->code},3,6\n"
                ."{$item->code},2,6\n",
            );

            $preview = $service->preview($duplicates, $tenantId, null, $warehouseId, null);

            $this->assertFalse($preview->isValid());
            $this->assertSame(1, $preview->toArray()['invalid_rows']);
            $this->assertStringContainsString('Duplicate', implode(' ', $preview->rows[1]['errors']));

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('unsupported columns');
            $service->preview(
                $this->csv("item_code,opening_quantity,unit_cost,warehouse_id\n{$item->code},3,6,99\n"),
                $tenantId,
                null,
                $warehouseId,
                null,
            );
        });
    }

    private function csv(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('opening-stock.csv', $contents);
    }

    private function configureBaseUom(int $tenantId, Item $item): void
    {
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $item): void {
            $uomId = (int) DB::table('unit_of_measures')->insertGetId([
                'tenant_id' => $tenantId,
                'row_version' => 1,
                'code' => 'PCS',
                'name' => 'Pieces',
                'symbol' => 'pc',
                'type' => 'unit',
                'category' => 'quantity',
                'decimal_precision' => 0,
                'allow_fractional_quantity' => false,
                'is_base' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $item->forceFill(['base_uom_id' => $uomId])->save();
            $item->refresh();
        });
    }
}
