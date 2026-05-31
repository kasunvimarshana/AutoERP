<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\CustomerContacts;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\ListCustomerContactsServiceInterface;
use Modules\Customer\Application\Repositories\CustomerContactRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerDefaults;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class ListCustomerContactsService implements ListCustomerContactsServiceInterface
{
    public function __construct(
        private readonly CustomerContactRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : CustomerDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('customer.pagination.max_per_page', CustomerDefaults::MAX_PER_PAGE))
                : (int) config('customer.pagination.default_per_page', CustomerDefaults::DEFAULT_PER_PAGE);

            $criteria['tenant_id'] = $this->tenantId();

            if (isset($criteria['name']) && ! isset($criteria['contact_name'])) {
                $criteria['contact_name'] = $criteria['name'];
            }

            unset($criteria['search'], $criteria['name']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
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
