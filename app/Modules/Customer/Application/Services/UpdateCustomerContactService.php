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
        if ($contact->getCustomerId() !== $dto->customerId) {
            throw new CustomerContactNotFoundException($id);
        }

        $customer = $this->customerRepository->find($dto->customerId);
        if (! $customer || $customer->getTenantId() !== $contact->getTenantId()) {
            throw new CustomerNotFoundException($dto->customerId);
        }

        $contact->update(
            name: $dto->name,
            role: $dto->role,
            email: $dto->email,
            phone: $dto->phone,
            isPrimary: $dto->isPrimary,
        );

        if ($dto->isPrimary) {
            $this->customerContactRepository->clearPrimaryByCustomer(
                tenantId: $customer->getTenantId(),
                customerId: $dto->customerId,
                excludeId: $id,
            );
        }

        return $this->customerContactRepository->save($contact);
    }
}
