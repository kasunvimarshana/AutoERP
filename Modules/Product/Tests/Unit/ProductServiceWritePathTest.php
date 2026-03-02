<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Product\Application\DTOs\CreateProductDTO;
use Modules\Product\Application\Services\ProductService;
use PHPUnit\Framework\TestCase;

/**
 * Structural compliance tests for ProductService write-path methods.
 *
 * create(), update(), and delete() call DB::transaction() internally,
 * which requires a full Laravel bootstrap, so functional tests live in
 * feature tests. These pure-PHP tests verify method signatures and
 * DTO field-mapping contracts.
 */
class ProductServiceWritePathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_product_service_has_create_method(): void
    {
        $this->assertTrue(
            method_exists(ProductService::class, 'create'),
            'ProductService must expose a public create() method.'
        );
    }

    public function test_product_service_has_update_method(): void
    {
        $this->assertTrue(
            method_exists(ProductService::class, 'update'),
            'ProductService must expose a public update() method.'
        );
    }

    public function test_product_service_has_delete_method(): void
    {
        $this->assertTrue(
            method_exists(ProductService::class, 'delete'),
            'ProductService must expose a public delete() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_create_accepts_create_product_dto(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'create');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreateProductDTO::class, (string) $params[0]->getType());
    }

    public function test_update_accepts_id_and_data_array(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'update');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    public function test_delete_accepts_single_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'delete');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // CreateProductDTO — create payload mapping
    // -------------------------------------------------------------------------

    public function test_create_payload_maps_dto_fields_correctly(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'    => 'Paracetamol 500mg',
            'sku'     => 'PCM-500',
            'type'    => 'physical',
            'uom_id'  => 3,
        ]);

        $createPayload = [
            'name'                => $dto->name,
            'sku'                 => $dto->sku,
            'type'                => $dto->type,
            'description'         => $dto->description,
            'uom_id'              => $dto->uomId,
            'buying_uom_id'       => $dto->buyingUomId,
            'selling_uom_id'      => $dto->sellingUomId,
            'is_active'           => $dto->isActive,
            'has_serial_tracking' => $dto->hasSerialTracking,
            'has_batch_tracking'  => $dto->hasBatchTracking,
            'has_expiry_tracking' => $dto->hasExpiryTracking,
            'barcode'             => $dto->barcode,
        ];

        $this->assertSame('Paracetamol 500mg', $createPayload['name']);
        $this->assertSame('PCM-500', $createPayload['sku']);
        $this->assertSame('physical', $createPayload['type']);
        $this->assertSame(3, $createPayload['uom_id']);
        $this->assertTrue($createPayload['is_active']);
    }

    public function test_create_payload_optional_fields_default_to_null(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'   => 'Basic Widget',
            'sku'    => 'BW-001',
            'type'   => 'consumable',
            'uom_id' => 1,
        ]);

        $this->assertNull($dto->description);
        $this->assertNull($dto->buyingUomId);
        $this->assertNull($dto->sellingUomId);
        $this->assertNull($dto->barcode);
    }

    public function test_create_payload_pharma_tracking_flags_all_enabled(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'                => 'Drug X',
            'sku'                 => 'DRG-X',
            'type'                => 'physical',
            'uom_id'              => 2,
            'has_serial_tracking' => true,
            'has_batch_tracking'  => true,
            'has_expiry_tracking' => true,
        ]);

        $this->assertTrue($dto->hasSerialTracking);
        $this->assertTrue($dto->hasBatchTracking);
        $this->assertTrue($dto->hasExpiryTracking);
    }

    public function test_create_payload_pharma_tracking_flags_default_to_false(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'   => 'Non-pharma Widget',
            'sku'    => 'NP-001',
            'type'   => 'consumable',
            'uom_id' => 1,
        ]);

        $this->assertFalse($dto->hasSerialTracking);
        $this->assertFalse($dto->hasBatchTracking);
        $this->assertFalse($dto->hasExpiryTracking);
    }

    public function test_create_payload_uom_id_cast_to_int(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'   => 'Widget',
            'sku'    => 'WGT-001',
            'type'   => 'physical',
            'uom_id' => '7',
        ]);

        $this->assertSame(7, $dto->uomId);
        $this->assertIsInt($dto->uomId);
    }
}
