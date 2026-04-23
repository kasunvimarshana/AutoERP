<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\Payslip;

interface PayslipRepositoryInterface extends RepositoryInterface
{
    public function save(Payslip $payslip): Payslip;

    public function find(int|string $id, array $columns = ['*']): ?Payslip;

    public function findByEmployeeAndRun(int $tenantId, int $employeeId, int $payrollRunId): ?Payslip;

    /** @return Payslip[] */
    public function findByPayrollRun(int $tenantId, int $payrollRunId): array;
}
