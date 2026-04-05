<?php
declare(strict_types=1);
namespace Modules\User\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\User\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): User;
    public function update(int $id, array $data): ?User;
    public function delete(int $id): bool;
    public function verifyPassword(int $id, string $password): bool;
    public function changePassword(int $id, string $hashedPassword): bool;
    public function updateAvatar(int $id, ?string $avatarPath): bool;
}
