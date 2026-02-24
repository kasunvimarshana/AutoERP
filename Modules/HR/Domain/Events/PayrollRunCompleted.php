<?php

namespace Modules\HR\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class PayrollRunCompleted extends DomainEvent
{
    /**
     * @param  string  $payrollRunId
     * @param  string  $tenantId          Tenant that owns this payroll run.
     * @param  string  $totalGross        BCMath-formatted gross salary total.
     * @param  string  $totalDeductions   BCMath-formatted total deductions.
     * @param  string  $totalNet          BCMath-formatted net salary total.
     * @param  string  $periodLabel       Human-readable period label (e.g. "Jan 2026").
     */
    public function __construct(
        public readonly string $payrollRunId,
        public readonly string $tenantId        = '',
        public readonly string $totalGross      = '0',
        public readonly string $totalDeductions = '0',
        public readonly string $totalNet        = '0',
        public readonly string $periodLabel     = '',
    ) {
        parent::__construct();
    }
}
