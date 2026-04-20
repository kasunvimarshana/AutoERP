<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Product\Application\Services\CreateProductCategoryService;
use Modules\Product\Application\Services\DeleteProductCategoryService;
use Modules\Product\Application\Services\FindProductCategoryService;
use Modules\Product\Application\Services\UpdateProductCategoryService;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\Exceptions\ProductCategoryNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ProductCategoryServiceTest extends TestCase
{
    /** @var ProductCategoryRepositoryInterface&MockObject */
    private ProductCategoryRepositoryInterface $repository;

    /** @var SlugGeneratorInterface&MockObject */
    private SlugGeneratorInterface $slugGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ProductCategoryRepositoryInterface::class);
        $this->slugGenerator = $this->createMock(SlugGeneratorInterface::class);
    }

    public function test_create_product_category_service_maps_payload_and_saves(): void
    {
        $service = new CreateProductCategoryService($this->repository, $this->slugGenerator);

        $this->slugGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(null, 'Electronics', 'category')
            ->willReturn('electronics');

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (mixed $productCategory): bool {
                if (! $productCategory instanceof ProductCategory) {
                    return false;
                }

                return $productCategory->getTenantId() === 7
                    && $productCategory->getName() === 'Electronics'
                    && $productCategory->getSlug() === 'electronics';
            }))
            ->willReturn($this->buildProductCategory(300));

        $result = $service->execute([
            'tenant_id' => 7,
            'name' => 'Electronics',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(ProductCategory::class, $result);
        $this->assertSame(300, $result->getId());
    }

    public function test_find_product_category_service_applies_filters_sort_and_pagination(): void
    {
        $service = new FindProductCategoryService($this->repository);

        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository->expects($this->once())->method('resetCriteria')->willReturn($this->repository);
        $this->repository->expects($this->exactly(2))->method('where')->withAnyParameters()->willReturn($this->repository);
        $this->repository->expects($this->once())->method('orderBy')->with('name', 'desc')->willReturn($this->repository);
        $this->repository->expects($this->once())->method('paginate')->with(20, ['*'], 'page', 2)->willReturn($paginator);

        $result = $service->list(
            filters: [
                'tenant_id' => 7,
                'name' => 'el',
                'unknown' => 'ignored',
            ],
            perPage: 20,
            page: 2,
            sort: '-name',
        );

        $this->assertSame($paginator, $result);
    }

    public function test_update_product_category_service_throws_when_product_category_missing(): void
    {
        $service = new UpdateProductCategoryService($this->repository, $this->slugGenerator);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->slugGenerator
            ->expects($this->never())
            ->method('generate');

        $this->expectException(ProductCategoryNotFoundException::class);

        $service->execute([
            'id' => 999,
            'tenant_id' => 7,
            'name' => 'Missing',
            'slug' => 'missing',
        ]);
    }

    public function test_delete_product_category_service_throws_when_product_category_missing(): void
    {
        $service = new DeleteProductCategoryService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(555)
            ->willReturn(null);

        $this->expectException(ProductCategoryNotFoundException::class);

        $service->execute(['id' => 555]);
    }

    private function buildProductCategory(int $id): ProductCategory
    {
        return new ProductCategory(
            id: $id,
            tenantId: 7,
            name: 'Electronics',
            slug: 'electronics',
            isActive: true,
        );
    }
}
