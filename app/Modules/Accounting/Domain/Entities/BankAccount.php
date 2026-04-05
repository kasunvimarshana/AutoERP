<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;

class BankAccount
{
    // account_type: checking|savings|credit_card|line_of_credit|paypal|other
    // status: active|inactive|closed
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private ?int $accountId,   // FK to COA account
        private string $name,
        private string $bankName,
        private ?string $accountNumber,
        private string $accountType,
        private string $currency,
        private float $currentBalance,
        private bool $isActive,
        private ?string $description,
        private ?\DateTimeInterface $lastSyncedAt,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getAccountId(): ?int { return $this->accountId; }
    public function getName(): string { return $this->name; }
    public function getBankName(): string { return $this->bankName; }
    public function getAccountNumber(): ?string { return $this->accountNumber; }
    public function getAccountType(): string { return $this->accountType; }
    public function getCurrency(): string { return $this->currency; }
    public function getCurrentBalance(): float { return $this->currentBalance; }
    public function isActive(): bool { return $this->isActive; }
    public function getDescription(): ?string { return $this->description; }
    public function getLastSyncedAt(): ?\DateTimeInterface { return $this->lastSyncedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isCreditCard(): bool { return $this->accountType === 'credit_card'; }
    public function updateBalance(float $balance): void { $this->currentBalance = $balance; }
    public function deactivate(): void { $this->isActive = false; }
}
