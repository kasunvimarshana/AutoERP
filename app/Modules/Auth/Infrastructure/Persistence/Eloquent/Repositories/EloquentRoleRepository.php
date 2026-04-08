<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

final class EloquentRoleRepository extends EloquentRepository implements RoleRepositoryInterface
{
    public function __construct(RoleModel $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug, ?int $tenantId = null): mixed
    {
        $query = $this->model->newQuery()->where('slug', $slug);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
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
    public function findWithPermissions(int $id): mixed
    {
        return $this->model->newQuery()
            ->with('permissions')
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findWithUsers(int $id): mixed
    {
        return $this->model->newQuery()
            ->with('users')
            ->find($id);
    }
}
