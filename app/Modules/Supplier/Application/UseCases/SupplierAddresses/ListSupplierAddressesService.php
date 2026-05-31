<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases\SupplierAddresses;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\ListSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierAddressRepositoryInterface;
use Modules\Supplier\Domain\Constants\SupplierDefaults;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Throwable;

final class ListSupplierAddressesService implements ListSupplierAddressesServiceInterface
{
    public function __construct(
        private readonly SupplierAddressRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : SupplierDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('supplier.pagination.max_per_page', SupplierDefaults::MAX_PER_PAGE))
                : (int) config('supplier.pagination.default_per_page', SupplierDefaults::DEFAULT_PER_PAGE);

            $criteria['tenant_id'] = $this->tenantId();

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, $exception->getMessage()));
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
