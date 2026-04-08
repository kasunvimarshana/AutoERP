<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;

final class EloquentSupplierRepository extends EloquentRepository implements SupplierRepositoryInterface
{
    public function __construct(SupplierModel $model)
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
