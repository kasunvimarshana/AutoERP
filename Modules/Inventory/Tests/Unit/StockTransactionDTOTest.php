<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use Modules\Inventory\Application\DTOs\StockTransactionDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StockTransactionDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class StockTransactionDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_required_fields(): void
    {
        $dto = StockTransactionDTO::fromArray([
            'transaction_type' => 'receipt',
            'warehouse_id'     => 1,
            'product_id'       => 5,
            'uom_id'           => 2,
            'quantity'         => '50.0000',
            'unit_cost'        => '12.5000',
        ]);

        $this->assertSame('receipt', $dto->transactionType);
        $this->assertSame(1, $dto->warehouseId);
        $this->assertSame(5, $dto->productId);
        $this->assertSame(2, $dto->uomId);
        $this->assertSame('50.0000', $dto->quantity);
        $this->assertSame('12.5000', $dto->unitCost);
        $this->assertNull($dto->batchNumber);
        $this->assertNull($dto->lotNumber);
        $this->assertNull($dto->serialNumber);
        $this->assertNull($dto->expiryDate);
        $this->assertNull($dto->notes);
        $this->assertFalse($dto->isPharmaceuticalCompliant);
    }

    public function test_from_array_hydrates_optional_pharma_fields(): void
    {
        $dto = StockTransactionDTO::fromArray([
            'transaction_type'           => 'receipt',
            'warehouse_id'               => 1,
            'product_id'                 => 7,
            'uom_id'                     => 1,
            'quantity'                   => '100.0000',
            'unit_cost'                  => '5.0000',
            'batch_number'               => 'BATCH-2026-001',
            'lot_number'                 => 'LOT-A',
            'serial_number'              => 'SN-12345',
            'expiry_date'                => '2027-12-31',
            'notes'                      => 'Received from vendor',
            'is_pharmaceutical_compliant' => true,
        ]);

        $this->assertSame('BATCH-2026-001', $dto->batchNumber);
        $this->assertSame('LOT-A', $dto->lotNumber);
        $this->assertSame('SN-12345', $dto->serialNumber);
        $this->assertSame('2027-12-31', $dto->expiryDate);
        $this->assertSame('Received from vendor', $dto->notes);
        $this->assertTrue($dto->isPharmaceuticalCompliant);
    }

    public function test_quantity_is_stored_as_string_for_bcmath(): void
    {
        $dto = StockTransactionDTO::fromArray([
            'transaction_type' => 'adjustment',
            'warehouse_id'     => 1,
            'product_id'       => 1,
            'uom_id'           => 1,
            'quantity'         => '0.0001',
            'unit_cost'        => '0.0001',
        ]);

        $this->assertIsString($dto->quantity);
        $this->assertIsString($dto->unitCost);
        $this->assertSame('0.0001', $dto->quantity);
        $this->assertSame('0.0001', $dto->unitCost);
    }

    public function test_integer_ids_are_cast_correctly(): void
    {
        $dto = StockTransactionDTO::fromArray([
            'transaction_type' => 'shipment',
            'warehouse_id'     => '3',
            'product_id'       => '10',
            'uom_id'           => '5',
            'quantity'         => '1.0000',
            'unit_cost'        => '1.0000',
        ]);

        $this->assertIsInt($dto->warehouseId);
        $this->assertIsInt($dto->productId);
        $this->assertIsInt($dto->uomId);
        $this->assertSame(3, $dto->warehouseId);
        $this->assertSame(10, $dto->productId);
        $this->assertSame(5, $dto->uomId);
    }

    public function test_pharmaceutical_compliant_defaults_to_false(): void
    {
        $dto = StockTransactionDTO::fromArray([
            'transaction_type' => 'receipt',
            'warehouse_id'     => 1,
            'product_id'       => 1,
            'uom_id'           => 1,
            'quantity'         => '1.0000',
            'unit_cost'        => '1.0000',
        ]);

        $this->assertFalse($dto->isPharmaceuticalCompliant);
    }
}
