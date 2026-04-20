<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\DeleteSupplierAddressServiceInterface;
use Modules\Supplier\Domain\Exceptions\SupplierAddressNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierAddressRepositoryInterface;

class DeleteSupplierAddressService extends BaseService implements DeleteSupplierAddressServiceInterface
{
    public function __construct(private readonly SupplierAddressRepositoryInterface $supplierAddressRepository)
    {
        parent::__construct($supplierAddressRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $address = $this->supplierAddressRepository->find($id);

        if (! $address) {
            throw new SupplierAddressNotFoundException($id);
        }

        return $this->supplierAddressRepository->delete($id);
    }
}
