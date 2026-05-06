<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Domain\Contracts\CustomerUserSynchronizerInterface;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class UpdateCustomerService extends BaseService implements UpdateCustomerServiceInterface
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerUserSynchronizerInterface $customerUserSynchronizer,
    ) {
        parent::__construct($customerRepository);
    }

    protected function handle(array $data): Customer
    {
        $id = (int) ($data['id'] ?? 0);
        $customer = $this->customerRepository->find($id);

        if (! $customer) {
            throw new CustomerNotFoundException($id);
        }

        $dto = CustomerData::fromArray($data);

        if ($customer->getTenantId() !== $dto->tenantId) {
            throw new CustomerNotFoundException($id);
        }

        if ($dto->rowVersion !== $customer->getRowVersion()) {
            throw new ConcurrentModificationException('Customer', $id);
        }

        if ($dto->userId !== null && $dto->userId !== $customer->getUserId()) {
            throw new DomainException('Changing customer user association is not allowed.');
        }

        $customer->update(
            userId: $customer->getUserId(),
            customerCode: $dto->customerCode,
            name: $dto->name,
            type: $dto->type,
            orgUnitId: $dto->orgUnitId,
            taxNumber: $dto->taxNumber,
            registrationNumber: $dto->registrationNumber,
            currencyId: $dto->currencyId,
            creditLimit: $this->normalizeDecimal($dto->creditLimit),
            paymentTermsDays: $dto->paymentTermsDays,
            arAccountId: $dto->arAccountId,
            status: $dto->status,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        $saved = $this->customerRepository->save($customer);

        $this->customerUserSynchronizer->synchronizeForCustomerUpdate(
            tenantId: $saved->getTenantId(),
            userId: $saved->getUserId(),
            orgUnitId: $saved->getOrgUnitId(),
            userPayload: $dto->user,
        );

        return $saved;
    }

    private function normalizeDecimal(string $value): string
    {
        if (! is_numeric($value)) {
            throw new DomainException('Credit limit must be numeric.');
        }

        $normalized = number_format((float) $value, 6, '.', '');

        if ((float) $normalized < 0.0) {
            throw new DomainException('Credit limit cannot be negative.');
        }

        return $normalized;
    }
}
