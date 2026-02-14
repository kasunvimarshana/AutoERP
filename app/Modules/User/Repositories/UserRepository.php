<?php

namespace App\Modules\User\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return User::class;
    }

    /**
     * Get active users
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get users by role
     */
    public function getByRole(string $role): Collection
    {
        return $this->model->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })->get();
    }

    /**
     * Get users by branch
     */
    public function getByBranch(int $branchId): Collection
    {
        return $this->model->where('branch_id', $branchId)->get();
    }

    /**
     * Search users by name or email
     */
    public function search(string $term): Collection
    {
        return $this->model->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->get();
    }
}
