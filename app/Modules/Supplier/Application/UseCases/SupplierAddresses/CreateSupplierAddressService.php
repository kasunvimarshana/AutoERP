<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases\SupplierAddresses;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\CreateSupplierAddressServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierAddressRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Throwable;

final class CreateSupplierAddressService implements CreateSupplierAddressServiceInterface
{
    public function __construct(
        private readonly SupplierAddressRepositoryInterface $repository,
        private readonly SupplierRepositoryInterface $suppliers,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    public function execute(array $payload): Result
    {
        try {
            $tenantId = $this->tenantId();
            $supplierId = (int) ($payload['supplier_id'] ?? 0);
            $supplier = $this->suppliers->findById($supplierId);

            if ($supplier === null || (int) $supplier->get('tenant_id') !== $tenantId) {
                return Result::failure(new Error(SupplierErrorCode::NOT_FOUND, 'Supplier not found.'));
            }

            $payload['tenant_id'] = $tenantId;
            $payload['organization_unit_id'] = $payload['organization_unit_id']
                ?? $this->currentOrganizationUnit->currentOrganizationUnitId();
            $payload['created_by'] = $this->currentUser->currentUserId();
            $payload['updated_by'] = $this->currentUser->currentUserId();
            $payload['row_version'] = $payload['row_version'] ?? 1;

            return Result::success($this->repository->create($payload));
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
