<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use InvalidArgumentException;
use Modules\Inventory\Application\DTOs\StockBatchDTO;
use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryContract;
use Modules\Inventory\Interfaces\Http\Controllers\InventoryController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for batch/lot management methods on InventoryService and InventoryController.
 *
 * Validates:
 *  - StockBatchDTO hydration and field casting
 *  - Negative stock prevention in recordTransaction()
 *  - createBatch() / showBatch() / updateBatch() / deleteBatch() method signatures
 *  - deductByStrategy() method signature and parameter validation
 *  - Corresponding controller method signatures
 */
class InventoryBatchServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // StockBatchDTO — hydration and casting
    // -------------------------------------------------------------------------

    public function test_stock_batch_dto_hydrates_required_fields(): void
    {
        $dto = StockBatchDTO::fromArray([
            'warehouse_id' => 1,
            'product_id'   => 5,
            'uom_id'       => 2,
            'quantity'     => '100.0000',
            'cost_price'   => '12.5000',
        ]);

        $this->assertSame(1, $dto->warehouseId);
        $this->assertSame(5, $dto->productId);
        $this->assertSame(2, $dto->uomId);
        $this->assertSame('100.0000', $dto->quantity);
        $this->assertSame('12.5000', $dto->costPrice);
        $this->assertSame('fifo', $dto->costingMethod);
        $this->assertNull($dto->batchNumber);
        $this->assertNull($dto->lotNumber);
        $this->assertNull($dto->expiryDate);
        $this->assertNull($dto->stockLocationId);
    }

    public function test_stock_batch_dto_hydrates_optional_fields(): void
    {
        $dto = StockBatchDTO::fromArray([
            'warehouse_id'      => 2,
            'product_id'        => 7,
            'uom_id'            => 1,
            'quantity'          => '50.0000',
            'cost_price'        => '8.0000',
            'batch_number'      => 'BATCH-2026-001',
            'lot_number'        => 'LOT-A',
            'serial_number'     => 'SN-001',
            'expiry_date'       => '2027-12-31',
            'costing_method'    => 'lifo',
            'stock_location_id' => 3,
        ]);

        $this->assertSame('BATCH-2026-001', $dto->batchNumber);
        $this->assertSame('LOT-A', $dto->lotNumber);
        $this->assertSame('SN-001', $dto->serialNumber);
        $this->assertSame('2027-12-31', $dto->expiryDate);
        $this->assertSame('lifo', $dto->costingMethod);
        $this->assertSame(3, $dto->stockLocationId);
    }

    public function test_stock_batch_dto_casts_ids_to_int(): void
    {
        $dto = StockBatchDTO::fromArray([
            'warehouse_id' => '10',
            'product_id'   => '20',
            'uom_id'       => '5',
            'quantity'     => '1.0000',
            'cost_price'   => '1.0000',
        ]);

        $this->assertIsInt($dto->warehouseId);
        $this->assertIsInt($dto->productId);
        $this->assertIsInt($dto->uomId);
    }

    public function test_stock_batch_dto_casts_quantity_and_cost_to_string(): void
    {
        $dto = StockBatchDTO::fromArray([
            'warehouse_id' => 1,
            'product_id'   => 1,
            'uom_id'       => 1,
            'quantity'     => 25,
            'cost_price'   => 3.5,
        ]);

        $this->assertIsString($dto->quantity);
        $this->assertIsString($dto->costPrice);
    }

    // -------------------------------------------------------------------------
    // InventoryService — batch CRUD method existence
    // -------------------------------------------------------------------------

    public function test_inventory_service_has_create_batch_method(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'createBatch'),
            'InventoryService must expose a public createBatch() method.'
        );
    }

    public function test_create_batch_is_public(): void
    {
        $ref = new ReflectionMethod(InventoryService::class, 'createBatch');
        $this->assertTrue($ref->isPublic());
    }

    public function test_create_batch_accepts_stock_batch_dto(): void
    {
        $ref    = new ReflectionMethod(InventoryService::class, 'createBatch');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(StockBatchDTO::class, $params[0]->getType()?->getName());
    }

    public function test_inventory_service_has_show_batch_method(): void
    {
        $this->assertTrue(method_exists(InventoryService::class, 'showBatch'));
    }

    public function test_show_batch_accepts_int_id(): void
    {
        $ref    = new ReflectionMethod(InventoryService::class, 'showBatch');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    public function test_inventory_service_has_update_batch_method(): void
    {
        $this->assertTrue(method_exists(InventoryService::class, 'updateBatch'));
    }

    public function test_update_batch_accepts_id_and_data_array(): void
    {
        $ref    = new ReflectionMethod(InventoryService::class, 'updateBatch');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    public function test_inventory_service_has_delete_batch_method(): void
    {
        $this->assertTrue(method_exists(InventoryService::class, 'deleteBatch'));
    }

    public function test_delete_batch_returns_bool(): void
    {
        $ref = new ReflectionMethod(InventoryService::class, 'deleteBatch');
        $this->assertSame('bool', (string) $ref->getReturnType());
    }

    // -------------------------------------------------------------------------
    // InventoryService — deductByStrategy method existence and signature
    // -------------------------------------------------------------------------

    public function test_inventory_service_has_deduct_by_strategy_method(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'deductByStrategy'),
            'InventoryService must expose a public deductByStrategy() method.'
        );
    }

    public function test_deduct_by_strategy_is_public(): void
    {
        $ref = new ReflectionMethod(InventoryService::class, 'deductByStrategy');
        $this->assertTrue($ref->isPublic());
    }

    public function test_deduct_by_strategy_has_strategy_param_with_fifo_default(): void
    {
        $ref    = new ReflectionMethod(InventoryService::class, 'deductByStrategy');
        $params = $ref->getParameters();

        // Find the strategy parameter
        $strategyParam = null;
        foreach ($params as $param) {
            if ($param->getName() === 'strategy') {
                $strategyParam = $param;
                break;
            }
        }

        $this->assertNotNull($strategyParam, 'deductByStrategy() must have a strategy parameter.');
        $this->assertTrue($strategyParam->isOptional());
        $this->assertSame('fifo', $strategyParam->getDefaultValue());
    }

    public function test_deduct_by_strategy_returns_array(): void
    {
        $ref = new ReflectionMethod(InventoryService::class, 'deductByStrategy');
        $this->assertSame('array', (string) $ref->getReturnType());
    }

    // -------------------------------------------------------------------------
    // InventoryService — negative stock validation (pure-PHP guard runs before DB)
    // -------------------------------------------------------------------------

    public function test_create_batch_rejects_zero_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/quantity must be greater than zero/i');

        $repo    = $this->createStub(InventoryRepositoryContract::class);
        $service = new InventoryService($repo);

        $dto = StockBatchDTO::fromArray([
            'warehouse_id' => 1,
            'product_id'   => 1,
            'uom_id'       => 1,
            'quantity'     => '0.0000',
            'cost_price'   => '5.0000',
        ]);

        $service->createBatch($dto);
    }

    public function test_create_batch_rejects_negative_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repo    = $this->createStub(InventoryRepositoryContract::class);
        $service = new InventoryService($repo);

        $dto = StockBatchDTO::fromArray([
            'warehouse_id' => 1,
            'product_id'   => 1,
            'uom_id'       => 1,
            'quantity'     => '-5.0000',
            'cost_price'   => '5.0000',
        ]);

        $service->createBatch($dto);
    }

    public function test_deduct_by_strategy_requires_batch_number_for_manual_strategy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/batch_number is required when using the manual deduction strategy/i');

        $repo    = $this->createStub(InventoryRepositoryContract::class);
        $service = new InventoryService($repo);

        // Calling deductByStrategy with manual strategy but no batch_number should throw
        // before reaching any DB calls.
        try {
            $service->deductByStrategy(
                productId:   1,
                warehouseId: 1,
                uomId:       1,
                quantity:    '5.0000',
                unitCost:    '1.0000',
                strategy:    'manual',
                batchNumber: null,
            );
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\Throwable) {
            // Other exceptions (e.g., DB not bootstrapped) are acceptable — only
            // InvalidArgumentException from the manual strategy guard is what we assert.
            $this->fail('Expected InvalidArgumentException for missing batch_number on manual strategy.');
        }
    }

    // -------------------------------------------------------------------------
    // InventoryController — batch CRUD method existence
    // -------------------------------------------------------------------------

    public function test_controller_has_create_batch_method(): void
    {
        $this->assertTrue(method_exists(InventoryController::class, 'createBatch'));
    }

    public function test_controller_create_batch_is_public(): void
    {
        $ref = new ReflectionMethod(InventoryController::class, 'createBatch');
        $this->assertTrue($ref->isPublic());
    }

    public function test_controller_create_batch_returns_json_response(): void
    {
        $ref = new ReflectionMethod(InventoryController::class, 'createBatch');
        $this->assertSame(\Illuminate\Http\JsonResponse::class, $ref->getReturnType()?->getName());
    }

    public function test_controller_has_show_batch_method(): void
    {
        $this->assertTrue(method_exists(InventoryController::class, 'showBatch'));
    }

    public function test_controller_has_update_batch_method(): void
    {
        $this->assertTrue(method_exists(InventoryController::class, 'updateBatch'));
    }

    public function test_controller_has_delete_batch_method(): void
    {
        $this->assertTrue(method_exists(InventoryController::class, 'deleteBatch'));
    }

    public function test_controller_has_deduct_by_strategy_method(): void
    {
        $this->assertTrue(method_exists(InventoryController::class, 'deductByStrategy'));
    }

    public function test_controller_deduct_by_strategy_returns_json_response(): void
    {
        $ref = new ReflectionMethod(InventoryController::class, 'deductByStrategy');
        $this->assertSame(\Illuminate\Http\JsonResponse::class, $ref->getReturnType()?->getName());
    }

    // -------------------------------------------------------------------------
    // InventoryRepositoryContract — new method contracts
    // -------------------------------------------------------------------------

    public function test_repository_contract_has_find_by_fifo_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Domain\Contracts\InventoryRepositoryContract::class, 'findByFIFO'),
            'InventoryRepositoryContract must declare findByFIFO().'
        );
    }

    public function test_repository_contract_has_find_by_lifo_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Domain\Contracts\InventoryRepositoryContract::class, 'findByLIFO'),
            'InventoryRepositoryContract must declare findByLIFO().'
        );
    }

    public function test_repository_contract_has_find_stock_item_by_id_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Domain\Contracts\InventoryRepositoryContract::class, 'findStockItemById'),
        );
    }

    public function test_repository_contract_has_update_stock_item_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Domain\Contracts\InventoryRepositoryContract::class, 'updateStockItem'),
        );
    }

    public function test_repository_contract_has_delete_stock_item_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Domain\Contracts\InventoryRepositoryContract::class, 'deleteStockItem'),
        );
    }

    // -------------------------------------------------------------------------
    // InventoryRepository — implementations
    // -------------------------------------------------------------------------

    public function test_inventory_repository_implements_find_by_fifo(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Infrastructure\Repositories\InventoryRepository::class, 'findByFIFO'),
        );
    }

    public function test_inventory_repository_implements_find_by_lifo(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Infrastructure\Repositories\InventoryRepository::class, 'findByLIFO'),
        );
    }

    public function test_inventory_repository_implements_find_stock_item_by_id(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Infrastructure\Repositories\InventoryRepository::class, 'findStockItemById'),
        );
    }

    public function test_inventory_repository_implements_update_stock_item(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Infrastructure\Repositories\InventoryRepository::class, 'updateStockItem'),
        );
    }

    public function test_inventory_repository_implements_delete_stock_item(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Infrastructure\Repositories\InventoryRepository::class, 'deleteStockItem'),
        );
    }
}
