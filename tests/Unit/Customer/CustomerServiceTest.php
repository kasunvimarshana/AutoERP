<?php

declare(strict_types=1);

namespace Tests\Unit\Customer;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Customer\Application\Services\CreateCustomerService;
use Modules\Customer\Application\Services\DeleteCustomerService;
use Modules\Customer\Application\Services\FindCustomerService;
use Modules\Customer\Application\Services\UpdateCustomerService;
use Modules\Customer\Domain\Contracts\CustomerUserSynchronizerInterface;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    /** @var CustomerRepositoryInterface&MockObject */
    private CustomerRepositoryInterface $repository;

    /** @var CustomerUserSynchronizerInterface&MockObject */
    private CustomerUserSynchronizerInterface $userSynchronizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(CustomerRepositoryInterface::class);
        $this->userSynchronizer = $this->createMock(CustomerUserSynchronizerInterface::class);
    }

    public function test_create_customer_service_maps_payload_and_saves(): void
    {
        $service = new CreateCustomerService($this->repository, $this->userSynchronizer);

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
            ->with($this->callback(function (mixed $customer): bool {
                if (! $customer instanceof Customer) {
                    return false;
                }

                return $customer->getTenantId() === 9
                    && $customer->getUserId() === 55
                    && $customer->getName() === 'Acme Ltd';
            }))
            ->willReturn($this->buildCustomer(701));

        $result = $service->execute([
            'tenant_id' => 9,
            'user_id' => 55,
            'name' => 'Acme Ltd',
            'type' => 'company',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(Customer::class, $result);
        $this->assertSame(701, $result->getId());
    }

    public function test_find_customer_service_applies_filters_sort_and_pagination(): void
    {
        $service = new FindCustomerService($this->repository);

        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository->expects($this->once())->method('resetCriteria')->willReturn($this->repository);
        $this->repository->expects($this->exactly(2))->method('where')->withAnyParameters()->willReturn($this->repository);
        $this->repository->expects($this->once())->method('orderBy')->with('customer_code', 'desc')->willReturn($this->repository);
        $this->repository->expects($this->once())->method('paginate')->with(20, ['*'], 'page', 2)->willReturn($paginator);

        $result = $service->list(
            filters: [
                'tenant_id' => 9,
                'customer_code' => 'CUS',
                'unknown' => 'ignored',
            ],
            perPage: 20,
            page: 2,
            sort: '-customer_code',
        );

        $this->assertSame($paginator, $result);
    }

    public function test_update_customer_service_throws_when_customer_missing(): void
    {
        $service = new UpdateCustomerService($this->repository, $this->userSynchronizer);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(CustomerNotFoundException::class);

        $service->execute([
            'id' => 999,
            'tenant_id' => 9,
            'name' => 'Acme Ltd',
        ]);
    }

    public function test_delete_customer_service_throws_when_customer_missing(): void
    {
        $service = new DeleteCustomerService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(555)
            ->willReturn(null);

        $this->expectException(CustomerNotFoundException::class);

        $service->execute(['id' => 555]);
    }

    public function test_update_customer_service_synchronizes_associated_user(): void
    {
        $service = new UpdateCustomerService($this->repository, $this->userSynchronizer);

        $existing = $this->buildCustomer(411);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(411)
            ->willReturn($existing);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Customer::class))
            ->willReturn($existing);

        $this->userSynchronizer
            ->expects($this->once())
            ->method('synchronizeForCustomerUpdate')
            ->with(9, 55, null, ['first_name' => 'Updated']);

        $result = $service->execute([
            'id' => 411,
            'tenant_id' => 9,
            'name' => 'Acme Ltd',
            'user' => ['first_name' => 'Updated'],
        ]);

        $this->assertInstanceOf(Customer::class, $result);
    }

    private function buildCustomer(int $id): Customer
    {
        return new Customer(
            id: $id,
            tenantId: 9,
            userId: 55,
            customerCode: 'CUS-001',
            name: 'Acme Ltd',
            type: 'company',
            orgUnitId: null,
            taxNumber: null,
            registrationNumber: null,
            currencyId: null,
            creditLimit: '0.000000',
            paymentTermsDays: 30,
            arAccountId: null,
            status: 'active',
            notes: null,
            metadata: null,
        );
    }
}
