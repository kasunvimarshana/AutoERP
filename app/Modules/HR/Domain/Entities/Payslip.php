<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

class Payslip
{
    /** @var PayslipLine[] */
    private array $lines = [];

    public function __construct(
        private readonly int $tenantId,
        private readonly int $employeeId,
        private readonly int $payrollRunId,
        private \DateTimeInterface $periodStart,
        private \DateTimeInterface $periodEnd,
        private string $grossSalary,
        private string $totalDeductions,
        private string $netSalary,
        private string $baseSalary,
        private float $workedDays,
        private string $status,
        private ?int $journalEntryId,
        private array $metadata,
        private readonly \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
        private ?int $id = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getPayrollRunId(): int
    {
        return $this->payrollRunId;
    }

    public function getPeriodStart(): \DateTimeInterface
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): \DateTimeInterface
    {
        return $this->periodEnd;
    }

    public function getGrossSalary(): string
    {
        return $this->grossSalary;
    }

    public function getTotalDeductions(): string
    {
        return $this->totalDeductions;
    }

    public function getNetSalary(): string
    {
        return $this->netSalary;
    }

    public function getBaseSalary(): string
    {
        return $this->baseSalary;
    }

    public function getWorkedDays(): float
    {
        return $this->workedDays;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getJournalEntryId(): ?int
    {
        return $this->journalEntryId;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function addLine(PayslipLine $line): void
    {
        $this->lines[] = $line;
    }

    /** @return PayslipLine[] */
    public function getLines(): array
    {
        return $this->lines;
    }
}
