<?php

declare(strict_types=1);

namespace Modules\Warehouse\Tests\Unit;

use Modules\Warehouse\Application\DTOs\CreatePickingOrderDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreatePickingOrderDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class CreatePickingOrderDTOTest extends TestCase
{
    public function test_from_array_hydrates_required_fields(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id' => 1,
            'picking_type' => 'batch',
            'lines'        => [
                ['product_id' => 5, 'quantity_requested' => '10.0000'],
            ],
        ]);

        $this->assertSame(1, $dto->warehouseId);
        $this->assertSame('batch', $dto->pickingType);
        $this->assertNull($dto->referenceType);
        $this->assertNull($dto->referenceId);
        $this->assertCount(1, $dto->lines);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id'  => 2,
            'picking_type'  => 'wave',
            'reference_type' => 'sales_order',
            'reference_id'  => 42,
            'lines'         => [],
        ]);

        $this->assertSame('sales_order', $dto->referenceType);
        $this->assertSame(42, $dto->referenceId);
        $this->assertIsInt($dto->referenceId);
    }

    public function test_warehouse_id_cast_to_int(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id' => '3',
            'picking_type' => 'zone',
            'lines'        => [],
        ]);

        $this->assertIsInt($dto->warehouseId);
        $this->assertSame(3, $dto->warehouseId);
    }

    public function test_line_quantities_stored_as_string_for_bcmath(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id' => 1,
            'picking_type' => 'batch',
            'lines'        => [
                ['product_id' => 10, 'quantity_requested' => '0.5000'],
                ['product_id' => 11, 'quantity_requested' => '100.0000'],
            ],
        ]);

        $this->assertIsString($dto->lines[0]['quantity_requested']);
        $this->assertIsString($dto->lines[1]['quantity_requested']);
        $this->assertSame('0.5000', $dto->lines[0]['quantity_requested']);
        $this->assertSame('100.0000', $dto->lines[1]['quantity_requested']);
    }

    public function test_line_product_ids_cast_to_int(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id' => 1,
            'picking_type' => 'batch',
            'lines'        => [
                ['product_id' => '7', 'quantity_requested' => '5.0000'],
            ],
        ]);

        $this->assertIsInt($dto->lines[0]['product_id']);
        $this->assertSame(7, $dto->lines[0]['product_id']);
    }
}
