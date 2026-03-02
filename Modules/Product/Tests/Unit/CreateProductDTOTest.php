<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Product\Application\DTOs\CreateProductDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateProductDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class CreateProductDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_required_fields(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'   => 'Widget A',
            'sku'    => 'WGT-A-001',
            'type'   => 'physical',
            'uom_id' => 1,
        ]);

        $this->assertSame('Widget A', $dto->name);
        $this->assertSame('WGT-A-001', $dto->sku);
        $this->assertSame('physical', $dto->type);
        $this->assertSame(1, $dto->uomId);
        $this->assertNull($dto->description);
        $this->assertNull($dto->buyingUomId);
        $this->assertNull($dto->sellingUomId);
        $this->assertTrue($dto->isActive);
        $this->assertFalse($dto->hasSerialTracking);
        $this->assertFalse($dto->hasBatchTracking);
        $this->assertFalse($dto->hasExpiryTracking);
        $this->assertNull($dto->barcode);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'               => 'Medicine X',
            'sku'                => 'MED-X-001',
            'type'               => 'physical',
            'description'        => 'Pharmaceutical product',
            'uom_id'             => 1,
            'buying_uom_id'      => 2,
            'selling_uom_id'     => 3,
            'is_active'          => false,
            'has_serial_tracking' => true,
            'has_batch_tracking'  => true,
            'has_expiry_tracking' => true,
            'barcode'            => '1234567890123',
        ]);

        $this->assertSame('Pharmaceutical product', $dto->description);
        $this->assertSame(2, $dto->buyingUomId);
        $this->assertSame(3, $dto->sellingUomId);
        $this->assertFalse($dto->isActive);
        $this->assertTrue($dto->hasSerialTracking);
        $this->assertTrue($dto->hasBatchTracking);
        $this->assertTrue($dto->hasExpiryTracking);
        $this->assertSame('1234567890123', $dto->barcode);
    }

    public function test_uom_id_is_cast_to_int(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'   => 'Product B',
            'sku'    => 'PROD-B',
            'type'   => 'service',
            'uom_id' => '4',
        ]);

        $this->assertIsInt($dto->uomId);
        $this->assertSame(4, $dto->uomId);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'   => 'Product C',
            'sku'    => 'PROD-C',
            'type'   => 'digital',
            'uom_id' => 1,
        ]);

        $this->assertTrue($dto->isActive);
    }

    public function test_tracking_flags_default_to_false(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'   => 'Product D',
            'sku'    => 'PROD-D',
            'type'   => 'consumable',
            'uom_id' => 1,
        ]);

        $this->assertFalse($dto->hasSerialTracking);
        $this->assertFalse($dto->hasBatchTracking);
        $this->assertFalse($dto->hasExpiryTracking);
    }

    public function test_optional_uom_ids_cast_to_int(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'            => 'Product E',
            'sku'             => 'PROD-E',
            'type'            => 'bundle',
            'uom_id'          => 1,
            'buying_uom_id'   => '5',
            'selling_uom_id'  => '6',
        ]);

        $this->assertIsInt($dto->buyingUomId);
        $this->assertIsInt($dto->sellingUomId);
        $this->assertSame(5, $dto->buyingUomId);
        $this->assertSame(6, $dto->sellingUomId);
    }
}
