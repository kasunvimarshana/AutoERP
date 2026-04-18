<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Services\CreateProductService;
use Modules\Product\Application\Services\DeleteProductService;
use Modules\Product\Application\Services\FindProductService;
use Modules\Product\Application\Services\UpdateProductService;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    /** @var ProductRepositoryInterface&MockObject */
    private ProductRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ProductRepositoryInterface::class);
    }

    public function test_create_product_service_maps_payload_and_saves(): void
    {
        $service = new CreateProductService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (mixed $product): bool {
                if (! $product instanceof Product) {
                    return false;
                }

                return $product->getTenantId() === 7
                    && $product->getName() === 'Widget'
                    && $product->getSlug() === 'widget'
                    && $product->getType() === 'physical';
            }))
            ->willReturn($this->buildProduct(100));

        $result = $service->execute([
            'tenant_id' => 7,
            'type' => 'physical',
            'name' => 'Widget',
            'slug' => 'widget',
            'base_uom_id' => 1,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertSame(100, $result->getId());
    }

    public function test_find_product_service_applies_filters_sort_and_pagination(): void
    {
        $service = new FindProductService($this->repository);

        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository->expects($this->once())->method('resetCriteria')->willReturn($this->repository);
        $this->repository->expects($this->exactly(2))->method('where')->withAnyParameters()->willReturn($this->repository);
        $this->repository->expects($this->once())->method('orderBy')->with('name', 'desc')->willReturn($this->repository);
        $this->repository->expects($this->once())->method('paginate')->with(20, ['*'], 'page', 2)->willReturn($paginator);

        $result = $service->list(
            filters: [
                'tenant_id' => 7,
                'name' => 'wid',
                'unknown' => 'ignored',
            ],
            perPage: 20,
            page: 2,
            sort: '-name',
        );

        $this->assertSame($paginator, $result);
    }

    public function test_update_product_service_throws_when_product_missing(): void
    {
        $service = new UpdateProductService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(ProductNotFoundException::class);

        $service->execute([
            'id' => 999,
            'tenant_id' => 7,
            'type' => 'physical',
            'name' => 'Missing',
            'slug' => 'missing',
            'base_uom_id' => 1,
        ]);
    }

    public function test_delete_product_service_throws_when_product_missing(): void
    {
        $service = new DeleteProductService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(555)
            ->willReturn(null);

        $this->expectException(ProductNotFoundException::class);

        $service->execute(['id' => 555]);
    }

    private function buildProduct(int $id): Product
    {
        return new Product(
            id: $id,
            tenantId: 7,
            type: 'physical',
            name: 'Widget',
            slug: 'widget',
            baseUomId: 1,
            isActive: true,
        );
    }
}
