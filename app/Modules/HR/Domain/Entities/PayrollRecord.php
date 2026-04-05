<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Entities;

use Modules\HR\Domain\Exceptions\InvalidPayrollException;

class PayrollRecord
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $employeeId,
        private int $periodYear,
        private int $periodMonth,
        private float $basicSalary,
        private float $allowances,
        private float $deductions,
        private float $taxAmount,
        private float $netSalary,
        private string $status,
        private ?\DateTimeInterface $paymentDate,
        private ?string $paymentReference,
        private ?array $breakdown,
        private ?int $processedById,
        private ?\DateTimeInterface $processedAt,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getEmployeeId(): int { return $this->employeeId; }
    public function getPeriodYear(): int { return $this->periodYear; }
    public function getPeriodMonth(): int { return $this->periodMonth; }
    public function getBasicSalary(): float { return $this->basicSalary; }
    public function getAllowances(): float { return $this->allowances; }
    public function getDeductions(): float { return $this->deductions; }
    public function getTaxAmount(): float { return $this->taxAmount; }
    public function getNetSalary(): float { return $this->netSalary; }
    public function getStatus(): string { return $this->status; }
    public function getPaymentDate(): ?\DateTimeInterface { return $this->paymentDate; }
    public function getPaymentReference(): ?string { return $this->paymentReference; }
    public function getBreakdown(): ?array { return $this->breakdown; }
    public function getProcessedById(): ?int { return $this->processedById; }
    public function getProcessedAt(): ?\DateTimeInterface { return $this->processedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function isDraft(): bool { return $this->status === self::STATUS_DRAFT; }
    public function isProcessed(): bool { return $this->status === self::STATUS_PROCESSED; }
    public function isApproved(): bool { return $this->status === self::STATUS_APPROVED; }
    public function isPaid(): bool { return $this->status === self::STATUS_PAID; }

    public function getGrossSalary(): float
    {
        return $this->basicSalary + $this->allowances;
    }

    public function process(int $processedById): void
    {
        if (!$this->isDraft()) {
            throw new InvalidPayrollException('Only draft payroll records can be processed.');
        }
        $this->status = self::STATUS_PROCESSED;
        $this->processedById = $processedById;
        $this->processedAt = new \DateTime();
    }

    public function approve(): void
    {
        if (!$this->isProcessed()) {
            throw new InvalidPayrollException('Only processed payroll records can be approved.');
        }
        $this->status = self::STATUS_APPROVED;
    }

    public function markAsPaid(\DateTimeInterface $paymentDate, string $reference): void
    {
        if (!$this->isApproved()) {
            throw new InvalidPayrollException('Only approved payroll records can be marked as paid.');
        }
        $this->status = self::STATUS_PAID;
        $this->paymentDate = $paymentDate;
        $this->paymentReference = $reference;
    }

    public function cancel(): void
    {
        if ($this->isPaid()) {
            throw new InvalidPayrollException('Paid payroll records cannot be cancelled.');
        }
        $this->status = self::STATUS_CANCELLED;
    }
}
