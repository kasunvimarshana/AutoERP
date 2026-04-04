<?php
namespace Modules\Supplier\Application\Services;

use Modules\Supplier\Application\Contracts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierContactData;
use Modules\Supplier\Domain\Entities\SupplierContact;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;

class UpdateSupplierContactService implements UpdateSupplierContactServiceInterface
{
    public function __construct(private readonly SupplierContactRepositoryInterface $repository) {}

    public function execute(int $id, SupplierContactData $data): SupplierContact
    {
        $contact = $this->repository->findById($id);
        if (!$contact) {
            throw new \DomainException("SupplierContact not found: {$id}");
        }
        return $this->repository->update($contact, $data->toArray());
    }
}
