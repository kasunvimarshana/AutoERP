<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\Contracts\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class EloquentWarehouseRepository extends EloquentRepository implements WarehouseRepositoryInterface
{
    public function __construct(WarehouseModel $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code): mixed
    {
        return $this->model->newQuery()->where('code', $code)->first();
    }

    public function findDefault(): mixed
    {
        return $this->model->newQuery()->where('is_default', true)->first();
    }
}
