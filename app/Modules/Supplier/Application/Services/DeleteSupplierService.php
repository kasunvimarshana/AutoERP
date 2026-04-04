<?php
namespace Modules\Supplier\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
use Modules\Supplier\Domain\Events\SupplierDeleted;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class DeleteSupplierService implements DeleteSupplierServiceInterface
{
    public function __construct(private readonly SupplierRepositoryInterface $repository) {}

    public function execute(int $id): bool
    {
        $supplier = $this->repository->findById($id);
        if (!$supplier) {
            throw new \DomainException("Supplier not found: {$id}");
        }
        $result = $this->repository->delete($supplier);
        if ($result) {
            Event::dispatch(new SupplierDeleted($supplier->tenantId, $supplier->id));
        }
        return $result;
    }
}
