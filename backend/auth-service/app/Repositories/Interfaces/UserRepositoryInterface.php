<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email, ?int $tenantId = null): ?User;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function delete(User $user): bool;

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection;
}
