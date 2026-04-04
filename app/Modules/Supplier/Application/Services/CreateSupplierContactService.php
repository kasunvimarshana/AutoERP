<?php
namespace Modules\Supplier\Application\Services;

use Modules\Supplier\Application\Contracts\CreateSupplierContactServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierContactData;
use Modules\Supplier\Domain\Entities\SupplierContact;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;

class CreateSupplierContactService implements CreateSupplierContactServiceInterface
{
    public function __construct(private readonly SupplierContactRepositoryInterface $repository) {}

    public function execute(SupplierContactData $data): SupplierContact
    {
        return $this->repository->create($data->toArray());
    }
}
