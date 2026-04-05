<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Events;

class PayrollProcessed
{
    public function __construct(public readonly int $payrollId, public readonly int $employeeId, public readonly int $periodYear, public readonly int $periodMonth) {}
}
