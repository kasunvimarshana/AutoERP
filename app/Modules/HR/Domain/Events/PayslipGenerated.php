<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\Payslip;

class PayslipGenerated extends BaseEvent
{
    public function __construct(
        public readonly Payslip $payslip,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'payslipId' => $this->payslip->getId(),
            'employeeId' => $this->payslip->getEmployeeId(),
            'payrollRunId' => $this->payslip->getPayrollRunId(),
            'netSalary' => $this->payslip->getNetSalary(),
        ]);
    }
}
