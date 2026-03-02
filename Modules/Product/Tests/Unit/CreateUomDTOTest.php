<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Product\Application\DTOs\CreateUomDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateUomDTO.
 */
class CreateUomDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_required_fields(): void
    {
        $dto = CreateUomDTO::fromArray([
            'name'   => 'Kilogram',
            'symbol' => 'kg',
        ]);

        $this->assertSame('Kilogram', $dto->name);
        $this->assertSame('kg', $dto->symbol);
        $this->assertTrue($dto->isActive);
    }

    public function test_from_array_accepts_is_active_false(): void
    {
        $dto = CreateUomDTO::fromArray([
            'name'      => 'Pound',
            'symbol'    => 'lb',
            'is_active' => false,
        ]);

        $this->assertFalse($dto->isActive);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $dto = CreateUomDTO::fromArray([
            'name'   => 'Litre',
            'symbol' => 'L',
        ]);

        $this->assertTrue($dto->isActive);
    }

    public function test_to_array_returns_correct_keys(): void
    {
        $dto = CreateUomDTO::fromArray([
            'name'      => 'Gram',
            'symbol'    => 'g',
            'is_active' => true,
        ]);

        $array = $dto->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('symbol', $array);
        $this->assertArrayHasKey('is_active', $array);
    }

    public function test_to_array_round_trips_correctly(): void
    {
        $data = [
            'name'      => 'Piece',
            'symbol'    => 'pcs',
            'is_active' => true,
        ];

        $dto   = CreateUomDTO::fromArray($data);
        $array = $dto->toArray();

        $this->assertSame($data['name'], $array['name']);
        $this->assertSame($data['symbol'], $array['symbol']);
        $this->assertSame($data['is_active'], $array['is_active']);
    }
}
