<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class PayrollRecord
{
    private ?int $id;

    private int $tenantId;

    private int $employeeId;

    private string $payPeriodStart;

    private string $payPeriodEnd;

    private float $grossSalary;

    private float $netSalary;

    private float $deductions;

    private float $allowances;

    private float $bonuses;

    private string $currency;

    private string $status;

    private ?string $notes;

    private Metadata $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $employeeId,
        string $payPeriodStart,
        string $payPeriodEnd,
        float $grossSalary,
        float $netSalary,
        float $deductions = 0.0,
        float $allowances = 0.0,
        float $bonuses = 0.0,
        string $currency = 'USD',
        string $status = 'draft',
        ?string $notes = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id             = $id;
        $this->tenantId       = $tenantId;
        $this->employeeId     = $employeeId;
        $this->payPeriodStart = $payPeriodStart;
        $this->payPeriodEnd   = $payPeriodEnd;
        $this->grossSalary    = $grossSalary;
        $this->netSalary      = $netSalary;
        $this->deductions     = $deductions;
        $this->allowances     = $allowances;
        $this->bonuses        = $bonuses;
        $this->currency       = $currency;
        $this->status         = $status;
        $this->notes          = $notes;
        $this->metadata       = $metadata ?? new Metadata([]);
        $this->createdAt      = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt      = $updatedAt ?? new \DateTimeImmutable;
    }

    public function process(): void
    {
        $this->status    = 'processed';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markAsPaid(): void
    {
        $this->status    = 'paid';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateDetails(
        string $payPeriodStart,
        string $payPeriodEnd,
        float $grossSalary,
        float $netSalary,
        float $deductions,
        float $allowances,
        float $bonuses,
        string $currency,
        ?string $notes
    ): void {
        $this->payPeriodStart = $payPeriodStart;
        $this->payPeriodEnd   = $payPeriodEnd;
        $this->grossSalary    = $grossSalary;
        $this->netSalary      = $netSalary;
        $this->deductions     = $deductions;
        $this->allowances     = $allowances;
        $this->bonuses        = $bonuses;
        $this->currency       = $currency;
        $this->notes          = $notes;
        $this->updatedAt      = new \DateTimeImmutable;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

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

    public function getPayPeriodStart(): string
    {
        return $this->payPeriodStart;
    }

    public function getPayPeriodEnd(): string
    {
        return $this->payPeriodEnd;
    }

    public function getGrossSalary(): float
    {
        return $this->grossSalary;
    }

    public function getNetSalary(): float
    {
        return $this->netSalary;
    }

    public function getDeductions(): float
    {
        return $this->deductions;
    }

    public function getAllowances(): float
    {
        return $this->allowances;
    }

    public function getBonuses(): float
    {
        return $this->bonuses;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getMetadata(): Metadata
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
}
