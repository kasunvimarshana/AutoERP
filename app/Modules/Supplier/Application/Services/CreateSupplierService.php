<?php
namespace Modules\Supplier\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Events\SupplierCreated;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class CreateSupplierService implements CreateSupplierServiceInterface
{
    public function __construct(private readonly SupplierRepositoryInterface $repository) {}

    public function execute(SupplierData $data): Supplier
    {
        $supplier = $this->repository->create($data->toArray());
        Event::dispatch(new SupplierCreated($supplier->tenantId, $supplier->id));
        return $supplier;
    }
}
