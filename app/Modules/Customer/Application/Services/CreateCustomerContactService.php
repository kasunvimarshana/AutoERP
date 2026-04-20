<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Customer\Application\Contracts\CreateCustomerContactServiceInterface;
use Modules\Customer\Application\DTOs\CustomerContactData;
use Modules\Customer\Domain\Entities\CustomerContact;
use Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerContactRepositoryInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class CreateCustomerContactService extends BaseService implements CreateCustomerContactServiceInterface
{
    public function __construct(
        private readonly CustomerContactRepositoryInterface $customerContactRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
        parent::__construct($customerContactRepository);
    }

    protected function handle(array $data): CustomerContact
    {
        $dto = CustomerContactData::fromArray($data);

        $customer = $this->customerRepository->find($dto->customer_id);
        if (! $customer) {
            throw new CustomerNotFoundException($dto->customer_id);
        }

        $contact = new CustomerContact(
            tenantId: $customer->getTenantId(),
            customerId: $dto->customer_id,
            name: $dto->name,
            role: $dto->role,
            email: $dto->email,
            phone: $dto->phone,
            isPrimary: $dto->is_primary,
        );

        if ($dto->is_primary) {
            $this->customerContactRepository->clearPrimaryByCustomer(
                tenantId: $customer->getTenantId(),
                customerId: $dto->customer_id,
            );
        }

        return $this->customerContactRepository->save($contact);
    }
}
