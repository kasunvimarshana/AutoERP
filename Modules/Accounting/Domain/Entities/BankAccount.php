<?php

namespace Modules\Accounting\Domain\Entities;

class BankAccount
{
    public function __construct(
        private readonly string  $id,
        private readonly string  $tenantId,
        private readonly string  $name,
        private readonly string  $accountNumber,
        private readonly string  $bankName,
        private readonly string  $currency,
        private readonly ?string $notes,
        private readonly bool    $isActive,
    ) {}

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getAccountNumber(): string { return $this->accountNumber; }
    public function getBankName(): string { return $this->bankName; }
    public function getCurrency(): string { return $this->currency; }
    public function getNotes(): ?string { return $this->notes; }
    public function isActive(): bool { return $this->isActive; }
}
