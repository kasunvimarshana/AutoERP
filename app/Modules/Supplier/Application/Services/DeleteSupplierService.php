<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
use Modules\Supplier\Domain\Events\SupplierDeleted;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class DeleteSupplierService extends BaseService implements DeleteSupplierServiceInterface
{
    public function __construct(private readonly SupplierRepositoryInterface $supplierRepository)
    {
        parent::__construct($supplierRepository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];
        $supplier = $this->supplierRepository->find($id);

        if (! $supplier) {
            throw new SupplierNotFoundException($id);
        }

        $tenantId = $supplier->getTenantId();
        $deleted = $this->supplierRepository->delete($id);

        if ($deleted) {
            $this->addEvent(new SupplierDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
