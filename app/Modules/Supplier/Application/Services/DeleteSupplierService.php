<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
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
        $id = (int) ($data['id'] ?? 0);
        $supplier = $this->supplierRepository->find($id);

        if (! $supplier) {
            throw new SupplierNotFoundException($id);
        }

        return $this->supplierRepository->delete($id);
    }
}
