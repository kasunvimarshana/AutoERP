<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\CRM\Domain\Contracts\Repositories\SupplierRepositoryInterface;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\SupplierModel;

class EloquentSupplierRepository extends EloquentRepository implements SupplierRepositoryInterface
{
    public function __construct(SupplierModel $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a supplier by its code within the current tenant scope.
     */
    public function findByCode(string $code): mixed
    {
        return $this->model->newQuery()->where('code', $code)->first();
    }
}
