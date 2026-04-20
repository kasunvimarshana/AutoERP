<?php

declare(strict_types=1);

namespace Tests\Unit\Supplier;

use Modules\Supplier\Application\Services\CreateSupplierService;
use Modules\Supplier\Application\Services\UpdateSupplierService;
use Modules\Supplier\Domain\Contracts\SupplierUserSynchronizerInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class SupplierServiceTest extends TestCase
{
    /** @var SupplierRepositoryInterface&MockObject */
    private SupplierRepositoryInterface $repository;

    /** @var SupplierUserSynchronizerInterface&MockObject */
    private SupplierUserSynchronizerInterface $userSynchronizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(SupplierRepositoryInterface::class);
        $this->userSynchronizer = $this->createMock(SupplierUserSynchronizerInterface::class);
    }

    public function test_create_supplier_service_maps_payload_and_saves(): void
    {
        $service = new CreateSupplierService($this->repository, $this->userSynchronizer);

        $this->userSynchronizer
            ->expects($this->once())
            ->method('resolveUserIdForCreate')
            ->with(9, null, 55, null)
            ->willReturn(55);

        $this->repository
            ->expects($this->once())
            ->method('findByTenantAndUserId')
            ->with(9, 55)
            ->willReturn(null);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (mixed $supplier): bool {
                if (! $supplier instanceof Supplier) {
                    return false;
                }

                return $supplier->getTenantId() === 9
                    && $supplier->getUserId() === 55
                    && $supplier->getSupplierCode() === 'SUP-001'
                    && $supplier->getName() === 'Acme Supplies';
            }))
            ->willReturn($this->buildSupplier(701));

        $result = $service->execute([
            'tenant_id' => 9,
            'user_id' => 55,
            'supplier_code' => 'SUP-001',
            'name' => 'Acme Supplies',
            'type' => 'company',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(Supplier::class, $result);
        $this->assertSame(701, $result->getId());
    }

    public function test_update_supplier_service_throws_when_supplier_missing(): void
    {
        $service = new UpdateSupplierService($this->repository, $this->userSynchronizer);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(SupplierNotFoundException::class);

        $service->execute([
            'id' => 999,
            'tenant_id' => 9,
            'name' => 'Acme Supplies',
            'type' => 'company',
        ]);
    }

    public function test_update_supplier_service_synchronizes_associated_user(): void
    {
        $service = new UpdateSupplierService($this->repository, $this->userSynchronizer);

        $existing = $this->buildSupplier(411);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(411)
            ->willReturn($existing);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Supplier::class))
            ->willReturn($existing);

        $this->userSynchronizer
            ->expects($this->once())
            ->method('synchronizeForSupplierUpdate')
            ->with(9, 55, null, ['first_name' => 'Updated']);

        $result = $service->execute([
            'id' => 411,
            'tenant_id' => 9,
            'supplier_code' => 'SUP-001',
            'name' => 'Acme Supplies',
            'type' => 'company',
            'status' => 'active',
            'user' => ['first_name' => 'Updated'],
        ]);

        $this->assertInstanceOf(Supplier::class, $result);
    }

    private function buildSupplier(int $id): Supplier
    {
        return new Supplier(
            id: $id,
            tenantId: 9,
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
}
