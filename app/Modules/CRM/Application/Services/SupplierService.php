<?php

declare(strict_types=1);

namespace Modules\CRM\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\CRM\Application\Contracts\SupplierServiceInterface;
use Modules\CRM\Domain\Contracts\Repositories\SupplierRepositoryInterface;
use Modules\CRM\Domain\Events\SupplierCreated;
use Modules\CRM\Domain\Exceptions\SupplierNotFoundException;

class SupplierService extends BaseService implements SupplierServiceInterface
{
    public function __construct(SupplierRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler — delegates to createSupplier.
     */
    protected function handle(array $data): mixed
    {
        return $this->createSupplier($data);
    }

    /**
     * Create a new supplier and dispatch a domain event.
     */
    public function createSupplier(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $supplier = $this->repository->create($data);
            $this->addEvent(new SupplierCreated((int) ($supplier->tenant_id ?? 0), $supplier->id));
            $this->dispatchEvents();

            return $supplier;
        });
    }

    /**
     * Update an existing supplier.
     */
    public function updateSupplier(string $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $supplier = $this->repository->find($id);
            if (! $supplier) {
                throw new SupplierNotFoundException($id);
            }

            return $this->repository->update($id, $data);
        });
    }
}
