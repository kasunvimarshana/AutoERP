<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\DeleteSupplierProductServiceInterface;
use Modules\Supplier\Domain\Exceptions\SupplierProductNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierProductRepositoryInterface;

class DeleteSupplierProductService extends BaseService implements DeleteSupplierProductServiceInterface
{
    public function __construct(private readonly SupplierProductRepositoryInterface $supplierProductRepository)
    {
        parent::__construct($supplierProductRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $supplierProduct = $this->supplierProductRepository->find($id);

        if (! $supplierProduct) {
            throw new SupplierProductNotFoundException($id);
        }

        return $this->supplierProductRepository->delete($id);
    }
}
