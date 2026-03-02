<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit;

use Modules\Sales\Application\DTOs\CreateSalesOrderDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateSalesOrderDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class CreateSalesOrderDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_required_fields(): void
    {
        $dto = CreateSalesOrderDTO::fromArray([
            'customer_id'   => 5,
            'order_date'    => '2026-01-20',
            'currency_code' => 'USD',
            'lines'         => [
                [
                    'product_id'      => 10,
                    'uom_id'          => 1,
                    'quantity'        => '2.0000',
                    'unit_price'      => '49.9900',
                    'discount_amount' => '0.0000',
                    'tax_rate'        => '0.1000',
                ],
            ],
        ]);

        $this->assertSame(5, $dto->customerId);
        $this->assertSame('2026-01-20', $dto->orderDate);
        $this->assertSame('USD', $dto->currencyCode);
        $this->assertCount(1, $dto->lines);
        $this->assertNull($dto->notes);
        $this->assertNull($dto->warehouseId);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = CreateSalesOrderDTO::fromArray([
            'customer_id'   => 1,
            'order_date'    => '2026-01-01',
            'currency_code' => 'GBP',
            'lines'         => [],
            'notes'         => 'Urgent order',
            'warehouse_id'  => 7,
        ]);

        $this->assertSame('Urgent order', $dto->notes);
        $this->assertSame(7, $dto->warehouseId);
        $this->assertIsInt($dto->warehouseId);
    }

    public function test_customer_id_cast_to_int(): void
    {
        $dto = CreateSalesOrderDTO::fromArray([
            'customer_id'   => '12',
            'order_date'    => '2026-01-01',
            'currency_code' => 'EUR',
            'lines'         => [],
        ]);

        $this->assertIsInt($dto->customerId);
        $this->assertSame(12, $dto->customerId);
    }

    public function test_order_date_cast_to_string(): void
    {
        $dto = CreateSalesOrderDTO::fromArray([
            'customer_id'   => 1,
            'order_date'    => '2026-12-31',
            'currency_code' => 'USD',
            'lines'         => [],
        ]);

        $this->assertIsString($dto->orderDate);
        $this->assertSame('2026-12-31', $dto->orderDate);
    }

    public function test_currency_code_cast_to_string(): void
    {
        $dto = CreateSalesOrderDTO::fromArray([
            'customer_id'   => 1,
            'order_date'    => '2026-01-01',
            'currency_code' => 'JPY',
            'lines'         => [],
        ]);

        $this->assertIsString($dto->currencyCode);
        $this->assertSame('JPY', $dto->currencyCode);
    }

    public function test_lines_preserve_string_numeric_values(): void
    {
        $dto = CreateSalesOrderDTO::fromArray([
            'customer_id'   => 1,
            'order_date'    => '2026-01-01',
            'currency_code' => 'USD',
            'lines'         => [
                [
                    'product_id'      => 5,
                    'uom_id'          => 1,
                    'quantity'        => '100.0000',
                    'unit_price'      => '9.9999',
                    'discount_amount' => '0.5000',
                    'tax_rate'        => '0.0800',
                ],
            ],
        ]);

        $line = $dto->lines[0];
        $this->assertSame('100.0000', $line['quantity']);
        $this->assertSame('9.9999', $line['unit_price']);
        $this->assertSame('0.5000', $line['discount_amount']);
        $this->assertSame('0.0800', $line['tax_rate']);
    }
}
