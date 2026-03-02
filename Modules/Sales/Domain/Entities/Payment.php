<?php
declare(strict_types=1);
namespace Modules\Sales\Domain\Entities;
class Payment {
    public function __construct(
        private readonly int    $id,
        private readonly int    $tenantId,
        private readonly int    $saleId,
        private string          $amount,
        private string          $paymentMethod,
        private ?string         $referenceNumber,
        private ?string         $paymentDate,
        private ?string         $notes,
        private ?int            $receivedBy,
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getSaleId(): int { return $this->saleId; }
    public function getAmount(): string { return $this->amount; }
    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function getReferenceNumber(): ?string { return $this->referenceNumber; }
    public function getPaymentDate(): ?string { return $this->paymentDate; }
    public function getNotes(): ?string { return $this->notes; }
    public function getReceivedBy(): ?int { return $this->receivedBy; }
}
