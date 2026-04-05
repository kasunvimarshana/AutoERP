<?php
declare(strict_types=1);
namespace Modules\Authorization\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Domain\Entities\Permission;

interface PermissionRepositoryInterface
{
    public function findById(int $id): ?Permission;
    public function findAll(int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByModule(string $module): array;
    public function create(array $data): Permission;
    public function update(int $id, array $data): ?Permission;
    public function delete(int $id): bool;
}
