<?php
declare(strict_types=1);
namespace Modules\HR\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\Position;

interface PositionServiceInterface
{
    public function findById(int $id): Position;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByDepartment(int $departmentId): array;
    public function create(array $data): Position;
    public function update(int $id, array $data): Position;
    public function delete(int $id): void;
}
