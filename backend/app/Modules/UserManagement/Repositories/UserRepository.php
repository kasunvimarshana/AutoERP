<?php

namespace App\Modules\UserManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function getForTenant(int $tenantId)
    {
        return $this->model->forTenant($tenantId)->get();
    }

    public function getActive()
    {
        return $this->model->active()->get();
    }

    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['tenant_id'])) {
            $query->forTenant($criteria['tenant_id']);
        }

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['role'])) {
            $query->where('role', $criteria['role']);
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        return $query->paginate($criteria['per_page'] ?? 15);
    }

    public function updateLastLogin(int $id)
    {
        return $this->update($id, ['last_login_at' => now()]);
    }

    public function countByTenant(int $tenantId): int
    {
        return $this->model->forTenant($tenantId)->count();
    }
}
