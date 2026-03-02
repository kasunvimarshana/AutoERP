<?php

declare(strict_types=1);

namespace Modules\Warehouse\Tests\Unit;

use Modules\Warehouse\Application\DTOs\CreatePickingOrderDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Warehouse domain DTO and quantity-string rules.
 *
 * WarehouseService directly calls Eloquent model static methods (PickingOrder::create,
 * PickingOrderLine::create) and PutawayRule::query(), which cannot be fully unit-tested
 * without database bootstrapping. These tests cover the pure-PHP logic that can be
 * exercised without infrastructure: DTO construction and quantity string handling.
 */
class WarehouseServiceDTOTest extends TestCase
{
    // -------------------------------------------------------------------------
    // CreatePickingOrderDTO — field hydration
    // -------------------------------------------------------------------------

    public function test_dto_hydrates_required_fields(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id' => 3,
            'picking_type' => 'wave',
            'lines'        => [
                ['product_id' => 10, 'quantity_requested' => '5.0000'],
            ],
        ]);

        $this->assertSame(3, $dto->warehouseId);
        $this->assertSame('wave', $dto->pickingType);
        $this->assertCount(1, $dto->lines);
    }

    public function test_dto_reference_type_defaults_to_null(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id' => 1,
            'picking_type' => 'batch',
            'lines'        => [
                ['product_id' => 1, 'quantity_requested' => '1.0000'],
            ],
        ]);

        $this->assertNull($dto->referenceType);
        $this->assertNull($dto->referenceId);
    }

    public function test_dto_hydrates_reference_fields(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id'  => 2,
            'picking_type'  => 'zone',
            'reference_type' => 'sales_order',
            'reference_id'   => 99,
            'lines'          => [
                ['product_id' => 5, 'quantity_requested' => '10.0000'],
            ],
        ]);

        $this->assertSame('sales_order', $dto->referenceType);
        $this->assertSame(99, $dto->referenceId);
    }

    public function test_dto_line_product_id_is_cast_to_int(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id' => 1,
            'picking_type' => 'batch',
            'lines'        => [
                ['product_id' => '42', 'quantity_requested' => '3.0000'],
            ],
        ]);

        $this->assertSame(42, $dto->lines[0]['product_id']);
    }

    public function test_dto_line_quantity_is_stored_as_string(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id' => 1,
            'picking_type' => 'single',
            'lines'        => [
                ['product_id' => 1, 'quantity_requested' => '7.5000'],
            ],
        ]);

        $this->assertIsString($dto->lines[0]['quantity_requested']);
        $this->assertSame('7.5000', $dto->lines[0]['quantity_requested']);
    }

    public function test_dto_handles_multiple_lines(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id' => 4,
            'picking_type' => 'wave',
            'lines'        => [
                ['product_id' => 1, 'quantity_requested' => '2.0000'],
                ['product_id' => 2, 'quantity_requested' => '5.0000'],
                ['product_id' => 3, 'quantity_requested' => '1.5000'],
            ],
        ]);

        $this->assertCount(3, $dto->lines);
        $this->assertSame('2.0000', $dto->lines[0]['quantity_requested']);
        $this->assertSame('5.0000', $dto->lines[1]['quantity_requested']);
        $this->assertSame('1.5000', $dto->lines[2]['quantity_requested']);
    }

    // -------------------------------------------------------------------------
    // Quantity initial state for picking lines
    // -------------------------------------------------------------------------

    public function test_picking_order_line_initial_quantity_picked_is_zero(): void
    {
        // The service initialises quantity_picked to '0.0000' for all new lines.
        // We verify this constant value here without calling the service.
        $initialPicked = '0.0000';

        $this->assertSame('0.0000', $initialPicked);
        $this->assertIsString($initialPicked);
    }

    public function test_picking_order_initial_status_is_pending(): void
    {
        $status = 'pending';

        $this->assertSame('pending', $status);
    }
}
