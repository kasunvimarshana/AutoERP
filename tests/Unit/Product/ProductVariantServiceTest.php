<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Services\CreateProductVariantService;
use Modules\Product\Application\Services\DeleteProductVariantService;
use Modules\Product\Application\Services\FindProductVariantService;
use Modules\Product\Application\Services\UpdateProductVariantService;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\Exceptions\ProductVariantNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ProductVariantServiceTest extends TestCase
{
    /** @var ProductVariantRepositoryInterface&MockObject */
    private ProductVariantRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ProductVariantRepositoryInterface::class);
    }

    public function test_create_product_variant_service_maps_payload_and_saves(): void
    {
        $service = new CreateProductVariantService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (mixed $productVariant): bool {
                if (! $productVariant instanceof ProductVariant) {
                    return false;
                }

                return $productVariant->getProductId() === 41
                    && $productVariant->getName() === 'Blue Variant'
                    && $productVariant->getSku() === 'BLU-001';
            }))
            ->willReturn($this->buildProductVariant(701));

        $result = $service->execute([
            'product_id' => 41,
            'name' => 'Blue Variant',
            'sku' => 'BLU-001',
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(ProductVariant::class, $result);
        $this->assertSame(701, $result->getId());
    }

    public function test_find_product_variant_service_applies_filters_sort_and_pagination(): void
    {
        $service = new FindProductVariantService($this->repository);

        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository->expects($this->once())->method('resetCriteria')->willReturn($this->repository);
        $this->repository->expects($this->exactly(2))->method('where')->withAnyParameters()->willReturn($this->repository);
        $this->repository->expects($this->once())->method('orderBy')->with('name', 'desc')->willReturn($this->repository);
        $this->repository->expects($this->once())->method('paginate')->with(20, ['*'], 'page', 2)->willReturn($paginator);

        $result = $service->list(
            filters: [
                'product_id' => 41,
                'name' => 'bl',
                'unknown' => 'ignored',
            ],
            perPage: 20,
            page: 2,
            sort: '-name',
        );

        $this->assertSame($paginator, $result);
    }

    public function test_update_product_variant_service_throws_when_variant_missing(): void
    {
        $service = new UpdateProductVariantService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(ProductVariantNotFoundException::class);

        $service->execute([
            'id' => 999,
            'product_id' => 41,
            'name' => 'Missing Variant',
            'sku' => 'MIS-999',
        ]);
    }

    public function test_delete_product_variant_service_throws_when_variant_missing(): void
    {
        $service = new DeleteProductVariantService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(555)
            ->willReturn(null);

        $this->expectException(ProductVariantNotFoundException::class);

        $service->execute(['id' => 555]);
    }

    private function buildProductVariant(int $id): ProductVariant
    {
        return new ProductVariant(
            id: $id,
            productId: 41,
            sku: 'BLU-001',
            name: 'Blue Variant',
            isDefault: false,
            isActive: true,
            metadata: ['color' => 'blue'],
        );
    }
}
