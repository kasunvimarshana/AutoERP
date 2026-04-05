<?php
declare(strict_types=1);
namespace Modules\HR\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\Department;

interface DepartmentServiceInterface
{
    public function findById(int $id): Department;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findAllByTenant(int $tenantId): array;
    public function create(array $data): Department;
    public function update(int $id, array $data): Department;
    public function delete(int $id): void;
}
