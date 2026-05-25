<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Application\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantPlanModel;

class EloquentTenantPlanRepository extends EloquentRepository implements TenantPlanRepositoryInterface
{
    public function __construct(TenantPlanModel $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name, array $with = []): ?Model
    {
        return $this->query($with)->where('name', $name)->first();
    }

    public function getActive(array $with = []): Collection
    {
        return $this->query($with)->where('is_active', true)->get();
    }

    public function getInactive(array $with = []): Collection
    {
        return $this->query($with)->where('is_active', false)->get();
    }
}

