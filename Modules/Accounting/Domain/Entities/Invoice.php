<?php

namespace Modules\Accounting\Domain\Entities;

use Modules\Accounting\Domain\Enums\InvoiceStatus;

class Invoice
{
    public function __construct(
        private readonly string        $id,
        private readonly string        $tenantId,
        private readonly string        $number,
        private readonly ?string       $partnerId,
        private readonly string        $partnerType,
        private readonly InvoiceStatus $status,
        private readonly array         $lines,
        private readonly string        $subtotal,
        private readonly string        $taxTotal,
        private readonly string        $total,
        private readonly string        $amountPaid,
        private readonly string        $amountDue,
        private readonly string        $currency,
        private readonly ?string       $dueDate,
        private readonly ?string       $createdBy,
    ) {}

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getNumber(): string { return $this->number; }
    public function getPartnerId(): ?string { return $this->partnerId; }
    public function getPartnerType(): string { return $this->partnerType; }
    public function getStatus(): InvoiceStatus { return $this->status; }
    public function getLines(): array { return $this->lines; }
    public function getSubtotal(): string { return $this->subtotal; }
    public function getTaxTotal(): string { return $this->taxTotal; }
    public function getTotal(): string { return $this->total; }
    public function getAmountPaid(): string { return $this->amountPaid; }
    public function getAmountDue(): string { return $this->amountDue; }
    public function getCurrency(): string { return $this->currency; }
    public function getDueDate(): ?string { return $this->dueDate; }
    public function getCreatedBy(): ?string { return $this->createdBy; }
}
