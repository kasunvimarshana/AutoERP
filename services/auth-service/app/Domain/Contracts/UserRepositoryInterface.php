<?php

namespace App\Domain\Contracts;

use App\Domain\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function find(string $id): ?User;
    public function findOrFail(string $id): User;
    public function findByEmail(string $email, ?string $tenantId = null): ?User;
    public function findAll(array $params = []): LengthAwarePaginator|Collection;
    public function create(array $data): User;
    public function update(string $id, array $data): User;
    public function delete(string $id): bool;
    public function findByTenant(string $tenantId, array $params = []): LengthAwarePaginator|Collection;
}
