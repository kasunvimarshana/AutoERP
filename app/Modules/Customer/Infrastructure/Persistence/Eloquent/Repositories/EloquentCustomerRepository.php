<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;

final class EloquentCustomerRepository extends EloquentRepository implements CustomerRepositoryInterface
{
    public function __construct(CustomerModel $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code, int $tenantId): mixed
    {
        return $this->model->newQuery()
            ->where('code', $code)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findByEmail(string $email, int $tenantId): mixed
    {
        return $this->model->newQuery()
            ->where('email', $email)
            ->where('tenant_id', $tenantId)
            ->first();
    }
}
