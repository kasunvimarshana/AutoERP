<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Services\CreateProductIdentifierService;
use Modules\Product\Application\Services\DeleteProductIdentifierService;
use Modules\Product\Application\Services\FindProductIdentifierService;
use Modules\Product\Application\Services\UpdateProductIdentifierService;
use Modules\Product\Domain\Entities\ProductIdentifier;
use Modules\Product\Domain\Exceptions\ProductIdentifierNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductIdentifierRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ProductIdentifierServiceTest extends TestCase
{
    /** @var ProductIdentifierRepositoryInterface&MockObject */
    private ProductIdentifierRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ProductIdentifierRepositoryInterface::class);
    }

    public function test_create_product_identifier_service_maps_payload_and_saves(): void
    {
        $service = new CreateProductIdentifierService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (mixed $productIdentifier): bool {
                if (! $productIdentifier instanceof ProductIdentifier) {
                    return false;
                }

                return $productIdentifier->getTenantId() === 9
                    && $productIdentifier->getProductId() === 41
                    && $productIdentifier->getTechnology() === 'barcode_1d'
                    && $productIdentifier->getValue() === 'ABC-123';
            }))
            ->willReturn($this->buildProductIdentifier(901));

        $result = $service->execute([
            'tenant_id' => 9,
            'product_id' => 41,
            'technology' => 'barcode_1d',
            'value' => 'ABC-123',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(ProductIdentifier::class, $result);
        $this->assertSame(901, $result->getId());
    }

    public function test_find_product_identifier_service_applies_filters_sort_and_pagination(): void
    {
        $service = new FindProductIdentifierService($this->repository);

        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository->expects($this->once())->method('resetCriteria')->willReturn($this->repository);
        $this->repository->expects($this->exactly(2))->method('where')->withAnyParameters()->willReturn($this->repository);
        $this->repository->expects($this->once())->method('orderBy')->with('value', 'desc')->willReturn($this->repository);
        $this->repository->expects($this->once())->method('paginate')->with(20, ['*'], 'page', 2)->willReturn($paginator);

        $result = $service->list(
            filters: [
                'tenant_id' => 9,
                'value' => 'ABC',
                'unknown' => 'ignored',
            ],
            perPage: 20,
            page: 2,
            sort: '-value',
        );

        $this->assertSame($paginator, $result);
    }

    public function test_update_product_identifier_service_throws_when_identifier_missing(): void
    {
        $service = new UpdateProductIdentifierService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(ProductIdentifierNotFoundException::class);

        $service->execute([
            'id' => 999,
            'tenant_id' => 9,
            'product_id' => 41,
            'technology' => 'barcode_1d',
            'value' => 'ABC-123',
        ]);
    }

    public function test_delete_product_identifier_service_throws_when_identifier_missing(): void
    {
        $service = new DeleteProductIdentifierService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(555)
            ->willReturn(null);

        $this->expectException(ProductIdentifierNotFoundException::class);

        $service->execute(['id' => 555]);
    }

    private function buildProductIdentifier(int $id): ProductIdentifier
    {
        return new ProductIdentifier(
            id: $id,
            tenantId: 9,
            productId: 41,
            technology: 'barcode_1d',
            value: 'ABC-123',
            isPrimary: true,
            isActive: true,
        );
    }
}
