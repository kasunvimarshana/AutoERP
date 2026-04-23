<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class PayslipData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $employeeId,
        public readonly int $payrollRunId,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly string $baseSalary = '0',
        public readonly float $workedDays = 0.0,
        public readonly array $metadata = [],
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            employeeId: (int) $data['employee_id'],
            payrollRunId: (int) $data['payroll_run_id'],
            periodStart: (string) $data['period_start'],
            periodEnd: (string) $data['period_end'],
            baseSalary: isset($data['base_salary']) ? (string) $data['base_salary'] : '0',
            workedDays: isset($data['worked_days']) ? (float) $data['worked_days'] : 0.0,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'payroll_run_id' => $this->payrollRunId,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'base_salary' => $this->baseSalary,
            'worked_days' => $this->workedDays,
            'metadata' => $this->metadata,
        ];
    }
}
