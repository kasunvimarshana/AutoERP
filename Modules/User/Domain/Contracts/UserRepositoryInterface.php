<?php
namespace Modules\User\Domain\Contracts;
interface UserRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByEmail(string $email): ?object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
    public function assignRole(string $userId, string $roleId): void;
    public function paginate(array $filters, int $perPage): object;
}
