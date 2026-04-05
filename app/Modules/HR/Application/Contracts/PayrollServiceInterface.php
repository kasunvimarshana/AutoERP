<?php
declare(strict_types=1);
namespace Modules\HR\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\PayrollRecord;

interface PayrollServiceInterface
{
    public function findById(int $id): PayrollRecord;
    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByTenantAndPeriod(int $tenantId, int $year, int $month): array;
    public function processPayroll(int $employeeId, int $year, int $month, int $processedById, array $overrides = []): PayrollRecord;
    public function approve(int $id): PayrollRecord;
    public function markAsPaid(int $id, string $paymentDate, string $reference): PayrollRecord;
    public function cancel(int $id): PayrollRecord;
    public function delete(int $id): void;
}
