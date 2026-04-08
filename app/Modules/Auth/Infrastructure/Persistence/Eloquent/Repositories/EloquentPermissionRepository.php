<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

final class EloquentPermissionRepository extends EloquentRepository implements PermissionRepositoryInterface
{
    public function __construct(PermissionModel $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug): mixed
    {
        return $this->model->newQuery()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByUuid(string $uuid): mixed
    {
        return $this->model->newQuery()
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByModule(string $module): mixed
    {
        return $this->model->newQuery()
            ->where('module', $module)
            ->get();
    }
}
