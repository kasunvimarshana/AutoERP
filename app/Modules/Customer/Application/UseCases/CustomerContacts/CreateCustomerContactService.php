<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\CustomerContacts;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\CreateCustomerContactServiceInterface;
use Modules\Customer\Application\Repositories\CustomerContactRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class CreateCustomerContactService implements CreateCustomerContactServiceInterface
{
    public function __construct(
        private readonly CustomerContactRepositoryInterface $repository,
        private readonly CustomerRepositoryInterface $customers,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    public function execute(array $payload): Result
    {
        try {
            $tenantId = $this->tenantId();
            $customerId = (int) ($payload['customer_id'] ?? 0);
            $customer = $this->customers->findById($customerId);

            if ($customer === null || (int) $customer->get('tenant_id') !== $tenantId) {
                return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'Customer not found.'));
            }

            $payload['tenant_id'] = $tenantId;
            $payload['organization_unit_id'] = $payload['organization_unit_id'] ?? $this->currentOrganizationUnit->currentOrganizationUnitId();
            $payload['created_by'] = $this->currentUser->currentUserId();
            $payload['updated_by'] = $this->currentUser->currentUserId();
            $payload['row_version'] = $payload['row_version'] ?? 1;

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function tenantId(): int
    {
        $tenantId = $this->currentTenant->currentTenantId();

        if ($tenantId === null || $tenantId < 1) {
            throw new \RuntimeException('Current tenant context is required.');
        }

        return $tenantId;
    }
}
