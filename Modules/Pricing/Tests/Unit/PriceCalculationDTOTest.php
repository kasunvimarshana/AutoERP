<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Unit;

use Modules\Pricing\Application\DTOs\PriceCalculationDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PriceCalculationDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class PriceCalculationDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_required_fields(): void
    {
        $dto = PriceCalculationDTO::fromArray([
            'product_id'    => 42,
            'quantity'      => '10.0000',
            'date'          => '2026-01-15',
        ]);

        $this->assertSame(42, $dto->productId);
        $this->assertSame('10.0000', $dto->quantity);
        $this->assertSame('2026-01-15', $dto->date);
        $this->assertNull($dto->uomId);
        $this->assertNull($dto->customerId);
        $this->assertNull($dto->locationId);
        $this->assertNull($dto->customerTier);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = PriceCalculationDTO::fromArray([
            'product_id'    => 10,
            'quantity'      => '5.0000',
            'date'          => '2026-02-01',
            'uom_id'        => 3,
            'customer_id'   => 99,
            'location_id'   => 7,
            'customer_tier' => 'gold',
        ]);

        $this->assertSame(3, $dto->uomId);
        $this->assertSame(99, $dto->customerId);
        $this->assertSame(7, $dto->locationId);
        $this->assertSame('gold', $dto->customerTier);
    }

    public function test_quantity_is_stored_as_string(): void
    {
        $dto = PriceCalculationDTO::fromArray([
            'product_id' => 1,
            'quantity'   => '2.5000',
            'date'       => '2026-01-01',
        ]);

        $this->assertIsString($dto->quantity);
        $this->assertSame('2.5000', $dto->quantity);
    }

    public function test_product_id_is_cast_to_int(): void
    {
        $dto = PriceCalculationDTO::fromArray([
            'product_id' => '15',
            'quantity'   => '1.0000',
            'date'       => '2026-01-01',
        ]);

        $this->assertSame(15, $dto->productId);
        $this->assertIsInt($dto->productId);
    }

    public function test_optional_ids_cast_to_int(): void
    {
        $dto = PriceCalculationDTO::fromArray([
            'product_id'  => 1,
            'quantity'    => '1.0000',
            'date'        => '2026-01-01',
            'uom_id'      => '4',
            'location_id' => '12',
        ]);

        $this->assertIsInt($dto->uomId);
        $this->assertSame(4, $dto->uomId);
        $this->assertIsInt($dto->locationId);
        $this->assertSame(12, $dto->locationId);
    }
}
