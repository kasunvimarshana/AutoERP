<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Customer\Application\Contracts\UpdateCustomerContactServiceInterface;
use Modules\Customer\Application\DTOs\CustomerContactData;
use Modules\Customer\Domain\Entities\CustomerContact;
use Modules\Customer\Domain\Exceptions\CustomerContactNotFoundException;
use Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerContactRepositoryInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class UpdateCustomerContactService extends BaseService implements UpdateCustomerContactServiceInterface
{
    public function __construct(
        private readonly CustomerContactRepositoryInterface $customerContactRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
        parent::__construct($customerContactRepository);
    }

    protected function handle(array $data): CustomerContact
    {
        $id = (int) ($data['id'] ?? 0);
        $contact = $this->customerContactRepository->find($id);
        if (! $contact) {
            throw new CustomerContactNotFoundException($id);
        }

        $dto = CustomerContactData::fromArray($data);
        if ($contact->getCustomerId() !== $dto->customer_id) {
            throw new CustomerContactNotFoundException($id);
        }

        $customer = $this->customerRepository->find($dto->customer_id);
        if (! $customer || $customer->getTenantId() !== $contact->getTenantId()) {
            throw new CustomerNotFoundException($dto->customer_id);
        }

        $contact->update(
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
                excludeId: $id,
            );
        }

        return $this->customerContactRepository->save($contact);
    }
}
