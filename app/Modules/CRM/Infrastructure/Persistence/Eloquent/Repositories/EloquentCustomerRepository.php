<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\CRM\Domain\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\CustomerModel;

class EloquentCustomerRepository extends EloquentRepository implements CustomerRepositoryInterface
{
    public function __construct(CustomerModel $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a customer by its code within the current tenant scope.
     */
    public function findByCode(string $code): mixed
    {
        return $this->model->newQuery()->where('code', $code)->first();
    }
}
