<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

final class EloquentUserRepository extends EloquentRepository implements UserRepositoryInterface
{
    public function __construct(UserModel $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): mixed
    {
        return $this->model->newQuery()
            ->where('email', $email)
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
    public function findWithRoles(int $id): mixed
    {
        return $this->model->newQuery()
            ->with(['roles.permissions'])
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByTenant(int $userId, int $tenantId): mixed
    {
        return $this->model->newQuery()
            ->where('id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();
    }
}
