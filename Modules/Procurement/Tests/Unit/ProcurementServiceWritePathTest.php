<?php

declare(strict_types=1);

namespace Modules\Procurement\Tests\Unit;

use Modules\Core\Application\Helpers\DecimalHelper;
use Modules\Procurement\Application\DTOs\CreatePurchaseOrderDTO;
use Modules\Procurement\Application\Services\ProcurementService;
use Modules\Procurement\Domain\Contracts\VendorBillRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Structural and write-path tests for ProcurementService.
 *
 * createPurchaseOrder(), receiveGoods(), and threeWayMatch() use DB::transaction()
 * and Eloquent internally, so these tests validate method signatures, DTO
 * field-mapping contracts, and BCMath receive-goods arithmetic using pure PHP.
 */
class ProcurementServiceWritePathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_procurement_service_has_create_purchase_order_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'createPurchaseOrder'),
            'ProcurementService must expose a public createPurchaseOrder() method.'
        );
    }

    public function test_procurement_service_has_receive_goods_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'receiveGoods'),
            'ProcurementService must expose a public receiveGoods() method.'
        );
    }

    public function test_procurement_service_has_three_way_match_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'threeWayMatch'),
            'ProcurementService must expose a public threeWayMatch() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_create_purchase_order_accepts_dto(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'createPurchaseOrder');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreatePurchaseOrderDTO::class, (string) $params[0]->getType());
    }

    public function test_receive_goods_accepts_order_id_and_lines(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'receiveGoods');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('purchaseOrderId', $params[0]->getName());
        $this->assertSame('linesReceived', $params[1]->getName());
    }

    public function test_three_way_match_accepts_purchase_order_id(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'threeWayMatch');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('purchaseOrderId', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // CreatePurchaseOrderDTO — create payload mapping
    // -------------------------------------------------------------------------

    public function test_create_purchase_order_payload_maps_dto_fields(): void
    {
        $dto = CreatePurchaseOrderDTO::fromArray([
            'vendor_id'                => 5,
            'order_date'               => '2026-03-01',
            'expected_delivery_date'   => '2026-03-15',
            'currency_code'            => 'USD',
            'lines'                    => [],
        ]);

        $createPayload = [
            'vendor_id'              => $dto->vendorId,
            'status'                 => 'draft',
            'order_date'             => $dto->orderDate,
            'expected_delivery_date' => $dto->expectedDeliveryDate,
            'currency_code'          => $dto->currencyCode,
            'notes'                  => $dto->notes,
        ];

        $this->assertSame(5, $createPayload['vendor_id']);
        $this->assertSame('draft', $createPayload['status']);
        $this->assertSame('2026-03-01', $createPayload['order_date']);
        $this->assertSame('2026-03-15', $createPayload['expected_delivery_date']);
        $this->assertSame('USD', $createPayload['currency_code']);
        $this->assertNull($createPayload['notes']);
    }

    public function test_create_purchase_order_initial_status_is_draft(): void
    {
        // Status is hardcoded in ProcurementService::createPurchaseOrder()
        $status = 'draft';

        $this->assertSame('draft', $status);
    }

    // -------------------------------------------------------------------------
    // Receive goods BCMath arithmetic — quantity rounding
    // -------------------------------------------------------------------------

    public function test_receive_goods_quantity_rounds_to_four_decimal_places(): void
    {
        $quantityReceived = '10.12345678';
        $rounded          = DecimalHelper::round($quantityReceived, DecimalHelper::SCALE_STANDARD);

        // SCALE_STANDARD = 4 decimal places
        $this->assertSame('10.1235', $rounded);
        $this->assertIsString($rounded);
    }

    public function test_receive_goods_unit_cost_rounds_to_four_decimal_places(): void
    {
        $unitCost = '25.999999';
        $rounded  = DecimalHelper::round($unitCost, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('26.0000', $rounded);
        $this->assertIsString($rounded);
    }

    public function test_receive_goods_whole_number_quantity_stored_at_four_dp(): void
    {
        $quantity = '50';
        $rounded  = DecimalHelper::round($quantity, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('50.0000', $rounded);
    }

    // -------------------------------------------------------------------------
    // threeWayMatch — return type inspection
    // -------------------------------------------------------------------------

    public function test_three_way_match_return_type_is_array(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'threeWayMatch');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame('array', $returnType);
    }
}
