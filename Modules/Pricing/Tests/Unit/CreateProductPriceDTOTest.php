<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Unit;

use Modules\Pricing\Application\DTOs\CreateProductPriceDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateProductPriceDTO.
 *
 * Validates field hydration, type coercion, optional defaults,
 * and toArray round-trip. No DB required.
 */
class CreateProductPriceDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_required_fields(): void
    {
        $dto = CreateProductPriceDTO::fromArray([
            'product_id'    => '7',
            'price_list_id' => '2',
            'uom_id'        => '1',
            'selling_price' => '99.9900',
        ]);

        $this->assertSame(7, $dto->productId);
        $this->assertSame(2, $dto->priceListId);
        $this->assertSame(1, $dto->uomId);
        $this->assertSame('99.9900', $dto->sellingPrice);
    }

    public function test_optional_fields_default_to_null(): void
    {
        $dto = CreateProductPriceDTO::fromArray([
            'product_id'    => 5,
            'price_list_id' => 1,
            'uom_id'        => 1,
            'selling_price' => '10.0000',
        ]);

        $this->assertNull($dto->costPrice);
        $this->assertNull($dto->minQuantity);
        $this->assertNull($dto->validFrom);
        $this->assertNull($dto->validTo);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = CreateProductPriceDTO::fromArray([
            'product_id'    => 3,
            'price_list_id' => 1,
            'uom_id'        => 1,
            'selling_price' => '50.0000',
            'cost_price'    => '35.0000',
            'min_quantity'  => '5.0000',
            'valid_from'    => '2026-01-01',
            'valid_to'      => '2026-12-31',
        ]);

        $this->assertSame('35.0000', $dto->costPrice);
        $this->assertSame('5.0000', $dto->minQuantity);
        $this->assertSame('2026-01-01', $dto->validFrom);
        $this->assertSame('2026-12-31', $dto->validTo);
    }

    public function test_to_array_round_trips_correctly(): void
    {
        $dto = CreateProductPriceDTO::fromArray([
            'product_id'    => 10,
            'price_list_id' => 3,
            'uom_id'        => 2,
            'selling_price' => '199.9900',
            'cost_price'    => '120.0000',
            'min_quantity'  => '1.0000',
            'valid_from'    => '2026-03-01',
            'valid_to'      => '2026-06-30',
        ]);

        $array = $dto->toArray();

        $this->assertSame(10, $array['product_id']);
        $this->assertSame(3, $array['price_list_id']);
        $this->assertSame(2, $array['uom_id']);
        $this->assertSame('199.9900', $array['selling_price']);
        $this->assertSame('120.0000', $array['cost_price']);
        $this->assertSame('1.0000', $array['min_quantity']);
        $this->assertSame('2026-03-01', $array['valid_from']);
        $this->assertSame('2026-06-30', $array['valid_to']);
    }

    public function test_product_id_is_cast_to_integer(): void
    {
        $dto = CreateProductPriceDTO::fromArray([
            'product_id'    => '42',
            'price_list_id' => '1',
            'uom_id'        => '1',
            'selling_price' => '0.0100',
        ]);

        $this->assertIsInt($dto->productId);
        $this->assertSame(42, $dto->productId);
    }

    public function test_selling_price_is_stored_as_string(): void
    {
        $dto = CreateProductPriceDTO::fromArray([
            'product_id'    => 1,
            'price_list_id' => 1,
            'uom_id'        => 1,
            'selling_price' => '9999.9999',
        ]);

        $this->assertIsString($dto->sellingPrice);
    }
}
