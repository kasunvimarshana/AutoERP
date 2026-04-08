<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;

class SalesReturn
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly ?string $salesOrderId,
        public readonly string $customerId,
        public readonly string $warehouseId,
        public readonly string $reference,
        public readonly string $status,
        public readonly DateTimeInterface $returnDate,
        public readonly ?string $reason,
        public readonly float $totalAmount,
        public readonly ?string $creditMemoNumber,
        public readonly float $refundAmount,
        public readonly float $restockingFee,
        public readonly ?string $notes,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function approve(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            salesOrderId: $this->salesOrderId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            reference: $this->reference,
            status: 'approved',
            returnDate: $this->returnDate,
            reason: $this->reason,
            totalAmount: $this->totalAmount,
            creditMemoNumber: $this->creditMemoNumber,
            refundAmount: $this->refundAmount,
            restockingFee: $this->restockingFee,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }

    public function complete(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            salesOrderId: $this->salesOrderId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            reference: $this->reference,
            status: 'completed',
            returnDate: $this->returnDate,
            reason: $this->reason,
            totalAmount: $this->totalAmount,
            creditMemoNumber: $this->creditMemoNumber,
            refundAmount: $this->refundAmount,
            restockingFee: $this->restockingFee,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }

    public function cancel(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            salesOrderId: $this->salesOrderId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            reference: $this->reference,
            status: 'cancelled',
            returnDate: $this->returnDate,
            reason: $this->reason,
            totalAmount: $this->totalAmount,
            creditMemoNumber: $this->creditMemoNumber,
            refundAmount: $this->refundAmount,
            restockingFee: $this->restockingFee,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }
}
