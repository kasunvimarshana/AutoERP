<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Services;

use Modules\Procurement\Application\Commands\CreateSupplierCommand;
use Modules\Procurement\Application\Commands\DeleteSupplierCommand;
use Modules\Procurement\Application\Commands\UpdateSupplierCommand;
use Modules\Procurement\Application\Handlers\CreateSupplierHandler;
use Modules\Procurement\Application\Handlers\DeleteSupplierHandler;
use Modules\Procurement\Application\Handlers\UpdateSupplierHandler;
use Modules\Procurement\Domain\Contracts\SupplierRepositoryInterface;
use Modules\Procurement\Domain\Entities\Supplier;

/**
 * Service orchestrating all supplier management operations.
 *
 * Controllers must interact with the supplier domain exclusively through this
 * service. Read operations are fulfilled directly via the repository contract;
 * write operations are delegated to the appropriate command handlers.
 */
class SupplierService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly CreateSupplierHandler $createSupplierHandler,
        private readonly UpdateSupplierHandler $updateSupplierHandler,
        private readonly DeleteSupplierHandler $deleteSupplierHandler,
    ) {}

    /**
     * Retrieve a paginated list of suppliers for the given tenant.
     *
     * @return array{items: Supplier[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listSuppliers(int $tenantId, int $page, int $perPage): array
    {
        return $this->supplierRepository->findAll($tenantId, $page, $perPage);
    }

    /**
     * Find a single supplier by its identifier within the given tenant.
     */
    public function findSupplierById(int $supplierId, int $tenantId): ?Supplier
    {
        return $this->supplierRepository->findById($supplierId, $tenantId);
    }

    /**
     * Create a new supplier and return the persisted entity.
     */
    public function createSupplier(CreateSupplierCommand $command): Supplier
    {
        return $this->createSupplierHandler->handle($command);
    }

    /**
     * Update an existing supplier and return the updated entity.
     */
    public function updateSupplier(UpdateSupplierCommand $command): Supplier
    {
        return $this->updateSupplierHandler->handle($command);
    }

    /**
     * Delete a supplier.
     */
    public function deleteSupplier(DeleteSupplierCommand $command): void
    {
        $this->deleteSupplierHandler->handle($command);
    }
}
