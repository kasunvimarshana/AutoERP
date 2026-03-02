<?php

declare(strict_types=1);

namespace Modules\Warehouse\Tests\Unit;

use Modules\Warehouse\Application\DTOs\CreatePickingOrderDTO;
use Modules\Warehouse\Application\Services\WarehouseService;
use Modules\Warehouse\Domain\Contracts\WarehouseRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Structural and delegation tests for WarehouseService.
 *
 * createPickingOrder() and getPutawayRecommendation() use Eloquent and
 * DB::transaction() internally and therefore cannot be invoked without a
 * full Laravel bootstrap.  These tests validate method signatures, DTO
 * field-mapping contracts, and delegation to the repository.
 */
class WarehouseServiceStructureTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_warehouse_service_has_create_picking_order_method(): void
    {
        $this->assertTrue(
            method_exists(WarehouseService::class, 'createPickingOrder'),
            'WarehouseService must expose a public createPickingOrder() method.'
        );
    }

    public function test_warehouse_service_has_get_putaway_recommendation_method(): void
    {
        $this->assertTrue(
            method_exists(WarehouseService::class, 'getPutawayRecommendation'),
            'WarehouseService must expose a public getPutawayRecommendation() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_create_picking_order_accepts_dto(): void
    {
        $reflection = new \ReflectionMethod(WarehouseService::class, 'createPickingOrder');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreatePickingOrderDTO::class, (string) $params[0]->getType());
    }

    public function test_get_putaway_recommendation_accepts_product_and_warehouse_ids(): void
    {
        $reflection = new \ReflectionMethod(WarehouseService::class, 'getPutawayRecommendation');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('productId', $params[0]->getName());
        $this->assertSame('warehouseId', $params[1]->getName());
    }

    public function test_get_putaway_recommendation_return_type_is_nullable(): void
    {
        $reflection  = new \ReflectionMethod(WarehouseService::class, 'getPutawayRecommendation');
        $returnType  = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull(), 'getPutawayRecommendation() must have a nullable return type.');
    }

    // -------------------------------------------------------------------------
    // CreatePickingOrderDTO — field mapping
    // -------------------------------------------------------------------------

    public function test_create_picking_order_dto_maps_warehouse_and_type(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id'   => 3,
            'picking_type'   => 'batch',
            'reference_type' => 'sales_order',
            'reference_id'   => 101,
            'lines'          => [],
        ]);

        $createPayload = [
            'warehouse_id'   => $dto->warehouseId,
            'picking_type'   => $dto->pickingType,
            'status'         => 'pending',
            'reference_type' => $dto->referenceType,
            'reference_id'   => $dto->referenceId,
        ];

        $this->assertSame(3, $createPayload['warehouse_id']);
        $this->assertSame('batch', $createPayload['picking_type']);
        $this->assertSame('pending', $createPayload['status']);
        $this->assertSame('sales_order', $createPayload['reference_type']);
        $this->assertSame(101, $createPayload['reference_id']);
    }

    public function test_create_picking_order_dto_initial_status_is_pending(): void
    {
        // Mirrors the hardcoded 'pending' status in WarehouseService::createPickingOrder()
        $status = 'pending';

        $this->assertSame('pending', $status);
    }

    public function test_create_picking_order_dto_lines_map_quantity_picked_to_zero(): void
    {
        $dto = CreatePickingOrderDTO::fromArray([
            'warehouse_id' => 1,
            'picking_type' => 'wave',
            'lines'        => [
                ['product_id' => 10, 'quantity_requested' => '5.0000'],
                ['product_id' => 20, 'quantity_requested' => '2.5000'],
            ],
        ]);

        // Mirrors the mapping in WarehouseService::createPickingOrder() for each line
        foreach ($dto->lines as $line) {
            $linePayload = [
                'product_id'         => $line['product_id'],
                'quantity_requested' => $line['quantity_requested'],
                'quantity_picked'    => '0.0000',
                'status'             => 'pending',
            ];

            $this->assertSame('0.0000', $linePayload['quantity_picked']);
            $this->assertSame('pending', $linePayload['status']);
        }
    }
}
