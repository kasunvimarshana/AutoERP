<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Product\Application\DTOs\AddUomConversionDTO;
use Modules\Product\Application\DTOs\CreateUomDTO;
use Modules\Product\Application\Services\UomService;
use PHPUnit\Framework\TestCase;

/**
 * Structural compliance tests for UomService.
 *
 * Verifies method existence, signatures, and DTO payload mapping
 * without requiring a database or full Laravel bootstrap.
 */
class UomServiceStructureTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_uom_service_has_list_uoms_method(): void
    {
        $this->assertTrue(method_exists(UomService::class, 'listUoms'));
    }

    public function test_uom_service_has_create_uom_method(): void
    {
        $this->assertTrue(method_exists(UomService::class, 'createUom'));
    }

    public function test_uom_service_has_show_uom_method(): void
    {
        $this->assertTrue(method_exists(UomService::class, 'showUom'));
    }

    public function test_uom_service_has_update_uom_method(): void
    {
        $this->assertTrue(method_exists(UomService::class, 'updateUom'));
    }

    public function test_uom_service_has_delete_uom_method(): void
    {
        $this->assertTrue(method_exists(UomService::class, 'deleteUom'));
    }

    public function test_uom_service_has_add_conversion_method(): void
    {
        $this->assertTrue(method_exists(UomService::class, 'addConversion'));
    }

    public function test_uom_service_has_list_conversions_method(): void
    {
        $this->assertTrue(method_exists(UomService::class, 'listConversions'));
    }

    // -------------------------------------------------------------------------
    // Method signatures — reflection
    // -------------------------------------------------------------------------

    public function test_create_uom_accepts_create_uom_dto(): void
    {
        $reflection = new \ReflectionMethod(UomService::class, 'createUom');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreateUomDTO::class, (string) $params[0]->getType());
    }

    public function test_add_conversion_accepts_add_uom_conversion_dto(): void
    {
        $reflection = new \ReflectionMethod(UomService::class, 'addConversion');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(AddUomConversionDTO::class, (string) $params[0]->getType());
    }

    public function test_list_conversions_accepts_product_id_int(): void
    {
        $reflection = new \ReflectionMethod(UomService::class, 'listConversions');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('productId', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // DTO field mapping — create payload
    // -------------------------------------------------------------------------

    public function test_create_uom_dto_maps_name_and_symbol(): void
    {
        $dto = CreateUomDTO::fromArray([
            'name'   => 'Milligram',
            'symbol' => 'mg',
        ]);

        $payload = [
            'name'      => $dto->name,
            'symbol'    => $dto->symbol,
            'is_active' => $dto->isActive,
        ];

        $this->assertSame('Milligram', $payload['name']);
        $this->assertSame('mg', $payload['symbol']);
        $this->assertTrue($payload['is_active']);
    }

    public function test_add_conversion_dto_maps_all_fields(): void
    {
        $dto = AddUomConversionDTO::fromArray([
            'product_id'  => 10,
            'from_uom_id' => 2,
            'to_uom_id'   => 3,
            'factor'      => '0.00100000',
        ]);

        $this->assertSame(10, $dto->productId);
        $this->assertSame(2, $dto->fromUomId);
        $this->assertSame(3, $dto->toUomId);
        $this->assertSame('0.00100000', $dto->factor);
    }
}
