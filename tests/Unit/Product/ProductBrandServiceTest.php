<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Services\CreateProductBrandService;
use Modules\Product\Application\Services\DeleteProductBrandService;
use Modules\Product\Application\Services\FindProductBrandService;
use Modules\Product\Application\Services\UpdateProductBrandService;
use Modules\Product\Domain\Entities\ProductBrand;
use Modules\Product\Domain\Exceptions\ProductBrandNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductBrandRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ProductBrandServiceTest extends TestCase
{
    /** @var ProductBrandRepositoryInterface&MockObject */
    private ProductBrandRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ProductBrandRepositoryInterface::class);
    }

    public function test_create_product_brand_service_maps_payload_and_saves(): void
    {
        $service = new CreateProductBrandService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (mixed $productBrand): bool {
                if (! $productBrand instanceof ProductBrand) {
                    return false;
                }

                return $productBrand->getTenantId() === 7
                    && $productBrand->getName() === 'Acme'
                    && $productBrand->getSlug() === 'acme';
            }))
            ->willReturn($this->buildProductBrand(200));

        $result = $service->execute([
            'tenant_id' => 7,
            'name' => 'Acme',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(ProductBrand::class, $result);
        $this->assertSame(200, $result->getId());
    }

    public function test_find_product_brand_service_applies_filters_sort_and_pagination(): void
    {
        $service = new FindProductBrandService($this->repository);

        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository->expects($this->once())->method('resetCriteria')->willReturn($this->repository);
        $this->repository->expects($this->exactly(2))->method('where')->withAnyParameters()->willReturn($this->repository);
        $this->repository->expects($this->once())->method('orderBy')->with('name', 'desc')->willReturn($this->repository);
        $this->repository->expects($this->once())->method('paginate')->with(20, ['*'], 'page', 2)->willReturn($paginator);

        $result = $service->list(
            filters: [
                'tenant_id' => 7,
                'name' => 'ac',
                'unknown' => 'ignored',
            ],
            perPage: 20,
            page: 2,
            sort: '-name',
        );

        $this->assertSame($paginator, $result);
    }

    public function test_update_product_brand_service_throws_when_product_brand_missing(): void
    {
        $service = new UpdateProductBrandService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(ProductBrandNotFoundException::class);

        $service->execute([
            'id' => 999,
            'tenant_id' => 7,
            'name' => 'Missing',
            'slug' => 'missing',
        ]);
    }

    public function test_delete_product_brand_service_throws_when_product_brand_missing(): void
    {
        $service = new DeleteProductBrandService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(555)
            ->willReturn(null);

        $this->expectException(ProductBrandNotFoundException::class);

        $service->execute(['id' => 555]);
    }

    private function buildProductBrand(int $id): ProductBrand
    {
        return new ProductBrand(
            id: $id,
            tenantId: 7,
            name: 'Acme',
            slug: 'acme',
            isActive: true,
        );
    }
}
