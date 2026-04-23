<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

class PayslipLine
{
    public function __construct(
        private readonly int $payslipId,
        private readonly int $payrollItemId,
        private string $itemName,
        private string $itemCode,
        private string $type,
        private string $amount,
        private array $metadata,
        private readonly \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
        private ?int $id = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPayslipId(): int
    {
        return $this->payslipId;
    }

    public function getPayrollItemId(): int
    {
        return $this->payrollItemId;
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }

    public function getItemCode(): string
    {
        return $this->itemCode;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): string
    {
        return $this->amount;
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
}
