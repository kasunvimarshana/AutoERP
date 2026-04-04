<?php
namespace Modules\Supplier\Application\Services;

use Modules\Supplier\Application\Contracts\DeleteSupplierContactServiceInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;

class DeleteSupplierContactService implements DeleteSupplierContactServiceInterface
{
    public function __construct(private readonly SupplierContactRepositoryInterface $repository) {}

    public function execute(int $id): bool
    {
        $contact = $this->repository->findById($id);
        if (!$contact) {
            throw new \DomainException("SupplierContact not found: {$id}");
        }
        return $this->repository->delete($contact);
    }
}
