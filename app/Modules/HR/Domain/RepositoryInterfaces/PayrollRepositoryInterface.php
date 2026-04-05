<?php
declare(strict_types=1);
namespace Modules\HR\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\PayrollRecord;

interface PayrollRepositoryInterface
{
    public function findById(int $id): ?PayrollRecord;
    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByTenantAndPeriod(int $tenantId, int $year, int $month): array;
    public function findByEmployeeAndPeriod(int $employeeId, int $year, int $month): ?PayrollRecord;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): PayrollRecord;
    public function update(int $id, array $data): ?PayrollRecord;
    public function delete(int $id): bool;
}
