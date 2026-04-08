<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\Contracts\Repositories\OrganizationUnitRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;

class EloquentOrganizationUnitRepository extends EloquentRepository implements OrganizationUnitRepositoryInterface
{
    public function __construct(OrganizationUnitModel $model)
    {
        parent::__construct($model);
    }

    public function findRoots(): Collection
    {
        return $this->model->newQuery()->whereNull('parent_id')->get();
    }

    public function findChildren(string $parentId): Collection
    {
        return $this->model->newQuery()->where('parent_id', $parentId)->get();
    }
}
