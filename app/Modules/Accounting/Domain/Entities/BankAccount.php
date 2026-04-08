<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use DateTimeInterface;

class BankAccount
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $accountId,
        public readonly string $name,
        public readonly string $accountType,
        public readonly ?string $bankName,
        public readonly ?string $accountNumber,
        public readonly ?string $routingNumber,
        public readonly string $currencyCode,
        public readonly float $currentBalance,
        public readonly ?DateTimeInterface $lastReconciledAt,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isCreditCard(): bool { return $this->accountType === 'credit_card'; }

    public function updateBalance(float $newBalance): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            accountId: $this->accountId,
            name: $this->name,
            accountType: $this->accountType,
            bankName: $this->bankName,
            accountNumber: $this->accountNumber,
            routingNumber: $this->routingNumber,
            currencyCode: $this->currencyCode,
            currentBalance: $newBalance,
            lastReconciledAt: $this->lastReconciledAt,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }
}
