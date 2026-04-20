<?php

declare(strict_types=1);

namespace Tests\Unit\Supplier;

use Modules\Supplier\Application\Services\CreateSupplierProductService;
use Modules\Supplier\Application\Services\UpdateSupplierProductService;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Entities\SupplierProduct;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierProductRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class SupplierProductServiceTest extends TestCase
{
    /** @var SupplierProductRepositoryInterface&MockObject */
    private SupplierProductRepositoryInterface $supplierProductRepository;

    /** @var SupplierRepositoryInterface&MockObject */
    private SupplierRepositoryInterface $supplierRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplierProductRepository = $this->createMock(SupplierProductRepositoryInterface::class);
        $this->supplierRepository = $this->createMock(SupplierRepositoryInterface::class);
    }

    public function test_create_supplier_product_service_clears_previous_preferred_when_requested(): void
    {
        $service = new CreateSupplierProductService($this->supplierProductRepository, $this->supplierRepository);

        $supplier = $this->buildSupplier(301, 11);

        $this->supplierRepository
            ->expects($this->once())
            ->method('find')
            ->with(301)
            ->willReturn($supplier);

        $this->supplierProductRepository
            ->expects($this->once())
            ->method('clearPreferredByProductVariant')
            ->with(11, 501, null, null);

        $this->supplierProductRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(SupplierProduct::class))
            ->willReturn($this->buildSupplierProduct(701, 11, 301, 501));

        $result = $service->execute([
            'supplier_id' => 301,
            'product_id' => 501,
            'variant_id' => null,
            'min_order_qty' => '2.000000',
            'is_preferred' => true,
        ]);

        $this->assertInstanceOf(SupplierProduct::class, $result);
        $this->assertSame(701, $result->getId());
    }

    public function test_create_supplier_product_service_throws_when_supplier_missing(): void
    {
        $service = new CreateSupplierProductService($this->supplierProductRepository, $this->supplierRepository);

        $this->supplierRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(SupplierNotFoundException::class);

        $service->execute([
            'supplier_id' => 999,
            'product_id' => 501,
        ]);
    }

    public function test_update_supplier_product_service_clears_previous_preferred_with_exclusion(): void
    {
        $service = new UpdateSupplierProductService($this->supplierProductRepository, $this->supplierRepository);

        $supplierProduct = $this->buildSupplierProduct(711, 11, 301, 501);
        $supplier = $this->buildSupplier(301, 11);

        $this->supplierProductRepository
            ->expects($this->once())
            ->method('find')
            ->with(711)
            ->willReturn($supplierProduct);

        $this->supplierRepository
            ->expects($this->once())
            ->method('find')
            ->with(301)
            ->willReturn($supplier);

        $this->supplierProductRepository
            ->expects($this->once())
            ->method('clearPreferredByProductVariant')
            ->with(11, 501, null, 711);

        $this->supplierProductRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(SupplierProduct::class))
            ->willReturn($supplierProduct);

        $result = $service->execute([
            'id' => 711,
            'supplier_id' => 301,
            'product_id' => 501,
            'variant_id' => null,
            'min_order_qty' => '2.000000',
            'is_preferred' => true,
        ]);

        $this->assertInstanceOf(SupplierProduct::class, $result);
        $this->assertSame(711, $result->getId());
    }

    private function buildSupplier(int $id, int $tenantId): Supplier
    {
        return new Supplier(
            id: $id,
            tenantId: $tenantId,
            userId: 55,
            supplierCode: 'SUP-001',
            name: 'Acme Supplies',
            type: 'company',
            orgUnitId: null,
            taxNumber: null,
            registrationNumber: null,
            currencyId: null,
            paymentTermsDays: 30,
            apAccountId: null,
            status: 'active',
            notes: null,
            metadata: null,
        );
    }

    private function buildSupplierProduct(int $id, int $tenantId, int $supplierId, int $productId): SupplierProduct
    {
        return new SupplierProduct(
            id: $id,
            tenantId: $tenantId,
            supplierId: $supplierId,
            productId: $productId,
            variantId: null,
            supplierSku: null,
            leadTimeDays: null,
            minOrderQty: '1.000000',
            isPreferred: true,
            lastPurchasePrice: null,
        );
    }
}
