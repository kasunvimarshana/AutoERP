<?php

namespace App\Modules\User\Repositories;

use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserRepository implements UserRepositoryInterface
{
    private User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function getAllWithFilters(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['filter'])) {
            foreach ($filters['filter'] as $field => $value) {
                $query->where($field, $value);
            }
        }

        if (!empty($filters['sort'])) {
            $sorts = explode(',', $filters['sort']);
            foreach ($sorts as $sortColumn) {
                $direction = 'asc';
                if (str_starts_with($sortColumn, '-')) {
                    $direction = 'desc';
                    $sortColumn = ltrim($sortColumn, '-');
                }
                $query->orderBy($sortColumn, $direction);
            }
        } else {
            $query->latest();
        }

        $perPage = $filters['limit'] ?? 15;
        return $query->paginate($perPage);
    }

    public function findById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $user = $this->findById($id);
        $user->update($data);
        return $user;
    }

    public function delete(int $id): bool
    {
        $user = $this->findById($id);
        return $user->delete();
    }
}
