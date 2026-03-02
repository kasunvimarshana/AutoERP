<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Product\Application\DTOs\AddUomConversionDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AddUomConversionDTO.
 */
class AddUomConversionDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_fields(): void
    {
        $dto = AddUomConversionDTO::fromArray([
            'product_id'  => 5,
            'from_uom_id' => 1,
            'to_uom_id'   => 2,
            'factor'      => '12.00000000',
        ]);

        $this->assertSame(5, $dto->productId);
        $this->assertSame(1, $dto->fromUomId);
        $this->assertSame(2, $dto->toUomId);
        $this->assertSame('12.00000000', $dto->factor);
    }

    public function test_product_id_cast_to_int(): void
    {
        $dto = AddUomConversionDTO::fromArray([
            'product_id'  => '7',
            'from_uom_id' => '3',
            'to_uom_id'   => '4',
            'factor'      => '0.45359237',
        ]);

        $this->assertIsInt($dto->productId);
        $this->assertIsInt($dto->fromUomId);
        $this->assertIsInt($dto->toUomId);
        $this->assertSame(7, $dto->productId);
        $this->assertSame(3, $dto->fromUomId);
        $this->assertSame(4, $dto->toUomId);
    }

    public function test_factor_stored_as_bcmath_safe_string(): void
    {
        $dto = AddUomConversionDTO::fromArray([
            'product_id'  => 1,
            'from_uom_id' => 1,
            'to_uom_id'   => 2,
            'factor'      => '1000.00000000',
        ]);

        $this->assertIsString($dto->factor);
        $this->assertSame('1000.00000000', $dto->factor);
    }

    public function test_to_array_round_trips_correctly(): void
    {
        $data = [
            'product_id'  => 3,
            'from_uom_id' => 1,
            'to_uom_id'   => 2,
            'factor'      => '6.00000000',
        ];

        $dto   = AddUomConversionDTO::fromArray($data);
        $array = $dto->toArray();

        $this->assertSame($data['product_id'], $array['product_id']);
        $this->assertSame($data['from_uom_id'], $array['from_uom_id']);
        $this->assertSame($data['to_uom_id'], $array['to_uom_id']);
        $this->assertSame($data['factor'], $array['factor']);
    }
}
