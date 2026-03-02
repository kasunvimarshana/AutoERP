<?php

declare(strict_types=1);

namespace Modules\Procurement\Tests\Unit;

use Modules\Procurement\Application\DTOs\CreatePurchaseOrderDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreatePurchaseOrderDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class CreatePurchaseOrderDTOTest extends TestCase
{
    public function test_from_array_hydrates_required_fields(): void
    {
        $dto = CreatePurchaseOrderDTO::fromArray([
            'vendor_id'     => 3,
            'order_date'    => '2026-02-01',
            'currency_code' => 'USD',
            'lines'         => [
                [
                    'product_id' => 10,
                    'uom_id'     => 1,
                    'quantity'   => '100.0000',
                    'unit_cost'  => '5.5000',
                ],
            ],
        ]);

        $this->assertSame(3, $dto->vendorId);
        $this->assertSame('2026-02-01', $dto->orderDate);
        $this->assertSame('USD', $dto->currencyCode);
        $this->assertCount(1, $dto->lines);
        $this->assertNull($dto->expectedDeliveryDate);
        $this->assertNull($dto->notes);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = CreatePurchaseOrderDTO::fromArray([
            'vendor_id'               => 5,
            'order_date'              => '2026-03-01',
            'expected_delivery_date'  => '2026-03-15',
            'currency_code'           => 'EUR',
            'lines'                   => [],
            'notes'                   => 'Urgent order',
        ]);

        $this->assertSame('2026-03-15', $dto->expectedDeliveryDate);
        $this->assertSame('Urgent order', $dto->notes);
    }

    public function test_vendor_id_cast_to_int(): void
    {
        $dto = CreatePurchaseOrderDTO::fromArray([
            'vendor_id'     => '9',
            'order_date'    => '2026-01-01',
            'currency_code' => 'USD',
            'lines'         => [],
        ]);

        $this->assertIsInt($dto->vendorId);
        $this->assertSame(9, $dto->vendorId);
    }

    public function test_line_quantities_stored_as_string(): void
    {
        $dto = CreatePurchaseOrderDTO::fromArray([
            'vendor_id'     => 1,
            'order_date'    => '2026-01-01',
            'currency_code' => 'USD',
            'lines'         => [
                [
                    'product_id' => 1,
                    'uom_id'     => 1,
                    'quantity'   => '250.0000',
                    'unit_cost'  => '0.0100',
                ],
            ],
        ]);

        $line = $dto->lines[0];
        $this->assertIsString($line['quantity']);
        $this->assertIsString($line['unit_cost']);
        $this->assertSame('250.0000', $line['quantity']);
        $this->assertSame('0.0100', $line['unit_cost']);
    }
}
