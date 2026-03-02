<?php

declare(strict_types=1);

namespace Modules\POS\Tests\Unit;

use Modules\POS\Application\DTOs\CreatePOSTransactionDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreatePOSTransactionDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class CreatePOSTransactionDTOTest extends TestCase
{
    public function test_from_array_hydrates_required_fields(): void
    {
        $dto = CreatePOSTransactionDTO::fromArray([
            'session_id' => 1,
            'lines'      => [
                [
                    'product_id'      => 5,
                    'uom_id'          => 1,
                    'quantity'        => '2.0000',
                    'unit_price'      => '9.9900',
                    'discount_amount' => '0.0000',
                ],
            ],
            'payments' => [
                ['payment_method' => 'cash', 'amount' => '19.98'],
            ],
        ]);

        $this->assertSame(1, $dto->sessionId);
        $this->assertCount(1, $dto->lines);
        $this->assertCount(1, $dto->payments);
        $this->assertSame('0', $dto->discountAmount);
        $this->assertFalse($dto->isOffline);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = CreatePOSTransactionDTO::fromArray([
            'session_id'      => 2,
            'lines'           => [],
            'payments'        => [],
            'discount_amount' => '5.0000',
            'is_offline'      => true,
        ]);

        $this->assertSame('5.0000', $dto->discountAmount);
        $this->assertTrue($dto->isOffline);
    }

    public function test_discount_amount_defaults_to_zero_string(): void
    {
        $dto = CreatePOSTransactionDTO::fromArray([
            'session_id' => 1,
            'lines'      => [],
            'payments'   => [],
        ]);

        $this->assertIsString($dto->discountAmount);
        $this->assertSame('0', $dto->discountAmount);
    }

    public function test_is_offline_defaults_to_false(): void
    {
        $dto = CreatePOSTransactionDTO::fromArray([
            'session_id' => 1,
            'lines'      => [],
            'payments'   => [],
        ]);

        $this->assertFalse($dto->isOffline);
    }

    public function test_session_id_cast_to_int(): void
    {
        $dto = CreatePOSTransactionDTO::fromArray([
            'session_id' => '7',
            'lines'      => [],
            'payments'   => [],
        ]);

        $this->assertIsInt($dto->sessionId);
        $this->assertSame(7, $dto->sessionId);
    }

    public function test_monetary_values_stored_as_strings(): void
    {
        $dto = CreatePOSTransactionDTO::fromArray([
            'session_id'      => 1,
            'discount_amount' => '0.9999',
            'lines'           => [],
            'payments'        => [],
        ]);

        $this->assertIsString($dto->discountAmount);
        $this->assertSame('0.9999', $dto->discountAmount);
    }
}
