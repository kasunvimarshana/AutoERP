<?php

declare(strict_types=1);

namespace Tests\Unit\Customer;

use Modules\Customer\Application\Services\CreateCustomerAddressService;
use Modules\Customer\Application\Services\UpdateCustomerAddressService;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Entities\CustomerAddress;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class CustomerAddressServiceTest extends TestCase
{
    /** @var CustomerAddressRepositoryInterface&MockObject */
    private CustomerAddressRepositoryInterface $addressRepository;

    /** @var CustomerRepositoryInterface&MockObject */
    private CustomerRepositoryInterface $customerRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addressRepository = $this->createMock(CustomerAddressRepositoryInterface::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
    }

    public function test_create_customer_address_service_saves_address(): void
    {
        $service = new CreateCustomerAddressService($this->addressRepository, $this->customerRepository);

        $this->customerRepository
            ->expects($this->once())
            ->method('find')
            ->with(111)
            ->willReturn($this->buildCustomer(111));

        $this->addressRepository
            ->expects($this->once())
            ->method('clearDefaultByCustomerAndType')
            ->with(9, 111, 'billing', null);

        $this->addressRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(CustomerAddress::class))
            ->willReturn($this->buildAddress(1));

        $result = $service->execute([
            'customer_id' => 111,
            'type' => 'billing',
            'address_line1' => '123 Main St',
            'city' => 'Colombo',
            'postal_code' => '00100',
            'country_id' => 1,
            'is_default' => true,
        ]);

        $this->assertInstanceOf(CustomerAddress::class, $result);
        $this->assertSame(1, $result->getId());
    }

    public function test_update_customer_address_service_updates_existing_address(): void
    {
        $service = new UpdateCustomerAddressService($this->addressRepository, $this->customerRepository);

        $existing = $this->buildAddress(5);

        $this->addressRepository
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($existing);

        $this->customerRepository
            ->expects($this->once())
            ->method('find')
            ->with(111)
            ->willReturn($this->buildCustomer(111));

        $this->addressRepository
            ->expects($this->once())
            ->method('clearDefaultByCustomerAndType')
            ->with(9, 111, 'shipping', 5);

        $this->addressRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(CustomerAddress::class))
            ->willReturn($existing);

        $result = $service->execute([
            'id' => 5,
            'customer_id' => 111,
            'type' => 'shipping',
            'address_line1' => '456 High St',
            'city' => 'Kandy',
            'postal_code' => '20000',
            'country_id' => 1,
            'is_default' => true,
        ]);

        $this->assertInstanceOf(CustomerAddress::class, $result);
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

    private function buildAddress(int $id): CustomerAddress
    {
        return new CustomerAddress(
            id: $id,
            tenantId: 9,
            customerId: 111,
            type: 'billing',
            addressLine1: '123 Main St',
            city: 'Colombo',
            postalCode: '00100',
            countryId: 1,
        );
    }
}
