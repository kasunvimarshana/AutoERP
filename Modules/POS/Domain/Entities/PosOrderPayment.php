<?php

namespace Modules\POS\Domain\Entities;

class PosOrderPayment
{
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $orderId,
        private readonly string $paymentMethod,
        private readonly string $amount,
        private readonly ?string $reference,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getOrderId(): string { return $this->orderId; }
    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function getAmount(): string { return $this->amount; }
    public function getReference(): ?string { return $this->reference; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
