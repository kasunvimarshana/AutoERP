<?php

namespace App\Modules\User\Repositories;

use App\Base\BaseRepository;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository
{
    /**
     * UserRepository constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?Model
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find users by tenant
     */
    public function findByTenant(string $tenantId, array $columns = ['*'], array $relations = [])
    {
        return $this->model
            ->byTenant($tenantId)
            ->with($relations)
            ->get($columns);
    }

    /**
     * Get active users
     */
    public function getActive(array $columns = ['*'], array $relations = [])
    {
        return $this->model
            ->active()
            ->with($relations)
            ->get($columns);
    }

    /**
     * Activate user
     */
    public function activate(string $id): bool
    {
        return $this->update($id, ['status' => 'active']);
    }

    /**
     * Deactivate user
     */
    public function deactivate(string $id): bool
    {
        return $this->update($id, ['status' => 'inactive']);
    }
}
