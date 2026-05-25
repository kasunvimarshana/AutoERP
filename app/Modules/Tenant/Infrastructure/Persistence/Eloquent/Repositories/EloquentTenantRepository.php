<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class EloquentTenantRepository extends EloquentRepository implements TenantRepositoryInterface
{
    public function __construct(TenantModel $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name, array $with = []): ?Model
    {
        return $this->query($with)->where('name', $name)->first();
    }

    public function getByStatus(string $status, array $with = []): Collection
    {
        return $this->query($with)->where('status', $status)->get();
    }

    public function paginateByStatus(string $status, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->query($with)->where('status', $status)->paginate($perPage);
    }
}

