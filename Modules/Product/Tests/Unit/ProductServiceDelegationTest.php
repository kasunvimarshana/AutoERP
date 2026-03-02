<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Modules\Product\Application\DTOs\CreateProductDTO;
use Modules\Product\Application\Services\ProductService;
use Modules\Product\Domain\Contracts\ProductRepositoryContract;
use Modules\Product\Domain\Contracts\UomRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProductService delegation logic.
 *
 * The repository is mocked — no database or Laravel bootstrap required.
 * Write methods (create, update, delete) use DB::transaction() which requires
 * the Laravel facade; those paths are covered by feature tests.
 * Read methods (list, show) are tested here.
 */
class ProductServiceDelegationTest extends TestCase
{
    private function makeService(
        ?ProductRepositoryContract $productRepo = null,
        ?UomRepositoryContract $uomRepo = null,
    ): ProductService {
        return new ProductService(
            $productRepo ?? $this->createMock(ProductRepositoryContract::class),
            $uomRepo     ?? $this->createMock(UomRepositoryContract::class),
        );
    }

    // -------------------------------------------------------------------------
    // list — paginate delegation
    // -------------------------------------------------------------------------

    public function test_list_delegates_to_paginate_with_default_per_page(): void
    {
        $paginator = $this->createMock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

        $productRepo = $this->createMock(ProductRepositoryContract::class);
        $productRepo->expects($this->once())
            ->method('paginate')
            ->with(15)
            ->willReturn($paginator);

        $service = $this->makeService($productRepo);
        $result  = $service->list();

        $this->assertSame($paginator, $result);
    }

    public function test_list_passes_custom_per_page(): void
    {
        $paginator = $this->createMock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

        $productRepo = $this->createMock(ProductRepositoryContract::class);
        $productRepo->expects($this->once())
            ->method('paginate')
            ->with(50)
            ->willReturn($paginator);

        $service = $this->makeService($productRepo);
        $service->list(50);
    }

    // -------------------------------------------------------------------------
    // show — delegates to findOrFail
    // -------------------------------------------------------------------------

    public function test_show_delegates_to_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $productRepo = $this->createMock(ProductRepositoryContract::class);
        $productRepo->expects($this->once())
            ->method('findOrFail')
            ->with(1)
            ->willReturn($model);

        $service = $this->makeService($productRepo);
        $result  = $service->show(1);

        $this->assertSame($model, $result);
    }

    public function test_show_accepts_string_id(): void
    {
        $model = $this->createMock(Model::class);

        $productRepo = $this->createMock(ProductRepositoryContract::class);
        $productRepo->expects($this->once())
            ->method('findOrFail')
            ->with('sku-abc')
            ->willReturn($model);

        $service = $this->makeService($productRepo);
        $result  = $service->show('sku-abc');

        $this->assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // convertUom — same-UOM identity shortcut (no DB call needed)
    // -------------------------------------------------------------------------

    public function test_convert_uom_returns_same_quantity_when_uom_ids_match(): void
    {
        $service = $this->makeService();
        $result  = $service->convertUom('product-1', '25.0000', 1, 1);

        $this->assertSame('25.0000', $result);
    }

    public function test_convert_uom_identity_preserves_decimal_format(): void
    {
        $service = $this->makeService();
        $result  = $service->convertUom('product-2', '0.12500000', 3, 3);

        $this->assertSame('0.12500000', $result);
    }

    // -------------------------------------------------------------------------
    // CreateProductDTO — pharmaceutical tracking flags
    // -------------------------------------------------------------------------

    public function test_dto_pharma_flags_all_enabled(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'                 => 'Amoxicillin 500mg',
            'sku'                  => 'AMX-500',
            'type'                 => 'physical',
            'uom_id'               => 1,
            'has_serial_tracking'  => true,
            'has_batch_tracking'   => true,
            'has_expiry_tracking'  => true,
        ]);

        $this->assertTrue($dto->hasSerialTracking);
        $this->assertTrue($dto->hasBatchTracking);
        $this->assertTrue($dto->hasExpiryTracking);
    }

    public function test_dto_tracking_flags_default_to_false(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'   => 'Standard Widget',
            'sku'    => 'STD-WGT',
            'type'   => 'physical',
            'uom_id' => 1,
        ]);

        $this->assertFalse($dto->hasSerialTracking);
        $this->assertFalse($dto->hasBatchTracking);
        $this->assertFalse($dto->hasExpiryTracking);
    }

    public function test_dto_optional_uom_fields_are_null_by_default(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'   => 'Service Product',
            'sku'    => 'SVC-001',
            'type'   => 'service',
            'uom_id' => 1,
        ]);

        $this->assertNull($dto->buyingUomId);
        $this->assertNull($dto->sellingUomId);
        $this->assertNull($dto->barcode);
        $this->assertNull($dto->description);
    }

    public function test_dto_is_active_defaults_to_true(): void
    {
        $dto = CreateProductDTO::fromArray([
            'name'   => 'Active Product',
            'sku'    => 'ACT-001',
            'type'   => 'physical',
            'uom_id' => 1,
        ]);

        $this->assertTrue($dto->isActive);
    }
}
