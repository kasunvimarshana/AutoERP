<?php

namespace Modules\POS\Domain\Entities;

use Modules\POS\Domain\Enums\PosOrderStatus;
use Modules\POS\Domain\Enums\PaymentMethod;

class PosOrder
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private string $sessionId,
        private string $number,
        private ?string $customerId,
        private PosOrderStatus $status,
        private PaymentMethod $paymentMethod,
        private array $lines,
        private string $subtotal,
        private string $taxTotal,
        private string $total,
        private ?string $cashTendered,
        private ?string $changeAmount,
        private string $currency,
        private ?string $createdBy,
        private \DateTimeImmutable $createdAt,
    ) {}

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getSessionId(): string { return $this->sessionId; }
    public function getNumber(): string { return $this->number; }
    public function getCustomerId(): ?string { return $this->customerId; }
    public function getStatus(): PosOrderStatus { return $this->status; }
    public function getPaymentMethod(): PaymentMethod { return $this->paymentMethod; }
    public function getLines(): array { return $this->lines; }
    public function getSubtotal(): string { return $this->subtotal; }
    public function getTaxTotal(): string { return $this->taxTotal; }
    public function getTotal(): string { return $this->total; }
    public function getCashTendered(): ?string { return $this->cashTendered; }
    public function getChangeAmount(): ?string { return $this->changeAmount; }
    public function getCurrency(): string { return $this->currency; }
    public function getCreatedBy(): ?string { return $this->createdBy; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
