<?php

declare(strict_types=1);

namespace Tests\Unit\Customer;

use Modules\Customer\Application\Services\CreateCustomerContactService;
use Modules\Customer\Application\Services\UpdateCustomerContactService;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Entities\CustomerContact;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerContactRepositoryInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class CustomerContactServiceTest extends TestCase
{
    /** @var CustomerContactRepositoryInterface&MockObject */
    private CustomerContactRepositoryInterface $contactRepository;

    /** @var CustomerRepositoryInterface&MockObject */
    private CustomerRepositoryInterface $customerRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contactRepository = $this->createMock(CustomerContactRepositoryInterface::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
    }

    public function test_create_customer_contact_service_saves_contact(): void
    {
        $service = new CreateCustomerContactService($this->contactRepository, $this->customerRepository);

        $this->customerRepository
            ->expects($this->once())
            ->method('find')
            ->with(111)
            ->willReturn($this->buildCustomer(111));

        $this->contactRepository
            ->expects($this->once())
            ->method('clearPrimaryByCustomer')
            ->with(9, 111, null);

        $this->contactRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(CustomerContact::class))
            ->willReturn($this->buildContact(2));

        $result = $service->execute([
            'customer_id' => 111,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_primary' => true,
        ]);

        $this->assertInstanceOf(CustomerContact::class, $result);
        $this->assertSame(2, $result->getId());
    }

    public function test_update_customer_contact_service_updates_existing_contact(): void
    {
        $service = new UpdateCustomerContactService($this->contactRepository, $this->customerRepository);

        $existing = $this->buildContact(3);

        $this->contactRepository
            ->expects($this->once())
            ->method('find')
            ->with(3)
            ->willReturn($existing);

        $this->customerRepository
            ->expects($this->once())
            ->method('find')
            ->with(111)
            ->willReturn($this->buildCustomer(111));

        $this->contactRepository
            ->expects($this->once())
            ->method('clearPrimaryByCustomer')
            ->with(9, 111, 3);

        $this->contactRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(CustomerContact::class))
            ->willReturn($existing);

        $result = $service->execute([
            'id' => 3,
            'customer_id' => 111,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'is_primary' => true,
        ]);

        $this->assertInstanceOf(CustomerContact::class, $result);
    }

    private function buildCustomer(int $id): Customer
    {
        return new Customer(
            id: $id,
            tenantId: 9,
            userId: 55,
            name: 'Acme Ltd',
            type: 'company',
            creditLimit: '0.000000',
            paymentTermsDays: 30,
            status: 'active',
        );
    }

    private function buildContact(int $id): CustomerContact
    {
        return new CustomerContact(
            id: $id,
            tenantId: 9,
            customerId: 111,
            name: 'John Doe',
            email: 'john@example.com',
        );
    }
}
