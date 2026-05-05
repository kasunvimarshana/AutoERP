<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Domain\Entities;

final class RefundTransaction
{
    private string $id;
    private string $tenantId;
    private string $rentalTransactionId;
    private string $refundNumber;
    private string $grossAmount;
    private string $adjustmentAmount;
    private string $netRefundAmount;
    private string $status;
    private ?string $financeReferenceId;
    private \DateTime $createdAt;
    private ?\DateTime $processedAt;

    public function __construct(
        string $id,
        string $tenantId,
        string $rentalTransactionId,
        string $refundNumber,
        string $grossAmount,
        string $adjustmentAmount,
        string $netRefundAmount,
        string $status,
        ?string $financeReferenceId,
        \DateTime $createdAt,
        ?\DateTime $processedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->rentalTransactionId = $rentalTransactionId;
        $this->refundNumber = $refundNumber;
        $this->grossAmount = $grossAmount;
        $this->adjustmentAmount = $adjustmentAmount;
        $this->netRefundAmount = $netRefundAmount;
        $this->status = $status;
        $this->financeReferenceId = $financeReferenceId;
        $this->createdAt = $createdAt;
        $this->processedAt = $processedAt;
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getRentalTransactionId(): string { return $this->rentalTransactionId; }
    public function getRefundNumber(): string { return $this->refundNumber; }
    public function getGrossAmount(): string { return $this->grossAmount; }
    public function getAdjustmentAmount(): string { return $this->adjustmentAmount; }
    public function getNetRefundAmount(): string { return $this->netRefundAmount; }
    public function getStatus(): string { return $this->status; }
    public function getFinanceReferenceId(): ?string { return $this->financeReferenceId; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getProcessedAt(): ?\DateTime { return $this->processedAt; }

    public function markProcessed(string $financeReferenceId): void
    {
        $this->status = 'processed';
        $this->financeReferenceId = $financeReferenceId;
        $this->processedAt = new \DateTime();
    }

    public function markFailed(): void
    {
        $this->status = 'failed';
    }
}
