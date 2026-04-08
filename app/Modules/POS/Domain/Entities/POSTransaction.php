<?php

declare(strict_types=1);

namespace Modules\POS\Domain\Entities;

use DateTimeInterface;

class POSTransaction
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $sessionId,
        public readonly ?string $customerId,
        public readonly string $reference,
        public readonly string $status,
        public readonly float $subtotal,
        public readonly float $discountAmount,
        public readonly float $taxAmount,
        public readonly float $totalAmount,
        public readonly float $paidAmount,
        public readonly float $changeAmount,
        public readonly string $paymentMethod,
        public readonly DateTimeInterface $transactionDate,
        public readonly ?string $notes,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function complete(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            sessionId: $this->sessionId,
            customerId: $this->customerId,
            reference: $this->reference,
            status: 'completed',
            subtotal: $this->subtotal,
            discountAmount: $this->discountAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            paidAmount: $this->paidAmount,
            changeAmount: $this->changeAmount,
            paymentMethod: $this->paymentMethod,
            transactionDate: $this->transactionDate,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function void(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            sessionId: $this->sessionId,
            customerId: $this->customerId,
            reference: $this->reference,
            status: 'voided',
            subtotal: $this->subtotal,
            discountAmount: $this->discountAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            paidAmount: $this->paidAmount,
            changeAmount: $this->changeAmount,
            paymentMethod: $this->paymentMethod,
            transactionDate: $this->transactionDate,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function refund(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            sessionId: $this->sessionId,
            customerId: $this->customerId,
            reference: $this->reference,
            status: 'refunded',
            subtotal: $this->subtotal,
            discountAmount: $this->discountAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            paidAmount: $this->paidAmount,
            changeAmount: $this->changeAmount,
            paymentMethod: $this->paymentMethod,
            transactionDate: $this->transactionDate,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }
}
