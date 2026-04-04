<?php
namespace Modules\User\Domain\RepositoryInterfaces;

use Modules\User\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): bool;
    public function verifyPassword(User $user, string $password): bool;
    public function changePassword(User $user, string $newPassword): bool;
    public function updateAvatar(User $user, string $avatarPath): User;
}
