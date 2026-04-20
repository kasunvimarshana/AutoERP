<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class PaymentAllocation
{
    public function __construct(
        private int $paymentId,
        private string $invoiceType,
        private int $invoiceId,
        private float $allocatedAmount,
        private ?int $tenantId = null,
        private ?int $id = null,
        private ?\DateTimeInterface $createdAt = null,
        private ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function getInvoiceType(): string
    {
        return $this->invoiceType;
    }

    public function getInvoiceId(): int
    {
        return $this->invoiceId;
    }

    public function getAllocatedAmount(): float
    {
        return $this->allocatedAmount;
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
