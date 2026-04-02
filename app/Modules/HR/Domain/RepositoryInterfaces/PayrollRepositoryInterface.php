<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\PayrollRecord;

interface PayrollRepositoryInterface extends RepositoryInterface
{
    public function save(PayrollRecord $payrollRecord): PayrollRecord;

    /**
     * Return all payroll records for a given employee.
     *
     * @return array<int, PayrollRecord>
     */
    public function getByEmployee(int $employeeId): array;
}
