<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Domain\Contracts\CustomerUserSynchronizerInterface;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class CreateCustomerService extends BaseService implements CreateCustomerServiceInterface
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerUserSynchronizerInterface $customerUserSynchronizer,
    ) {
        parent::__construct($customerRepository);
    }

    protected function handle(array $data): Customer
    {
        $dto = CustomerData::fromArray($data);

        $resolvedUserId = $this->customerUserSynchronizer->resolveUserIdForCreate(
            tenantId: $dto->tenantId,
            orgUnitId: $dto->orgUnitId,
            requestedUserId: $dto->userId,
            userPayload: $dto->user,
        );

        $existingCustomer = $this->customerRepository->findByTenantAndUserId($dto->tenantId, $resolvedUserId);
        if ($existingCustomer !== null) {
            throw new DomainException('The user is already linked to another customer.');
        }

        $customer = new Customer(
            tenantId: $dto->tenantId,
            userId: $resolvedUserId,
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

        return $this->customerRepository->save($customer);
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
