<?php
namespace Modules\Supplier\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Events\SupplierUpdated;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class UpdateSupplierService implements UpdateSupplierServiceInterface
{
    public function __construct(private readonly SupplierRepositoryInterface $repository) {}

    public function execute(int $id, SupplierData $data): Supplier
    {
        $supplier = $this->repository->findById($id);
        if (!$supplier) {
            throw new \DomainException("Supplier not found: {$id}");
        }
        $updated = $this->repository->update($supplier, $data->toArray());
        Event::dispatch(new SupplierUpdated($updated->tenantId, $updated->id));
        return $updated;
    }
}
