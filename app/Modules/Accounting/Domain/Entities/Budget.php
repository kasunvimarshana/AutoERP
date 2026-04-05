<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;

class Budget
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private ?int $accountId,
        private ?int $expenseCategoryId,
        private string $name,
        private \DateTimeInterface $periodStart,
        private \DateTimeInterface $periodEnd,
        private float $amount,
        private float $spentAmount,
        private string $currency,
        private ?string $notes,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getAccountId(): ?int { return $this->accountId; }
    public function getExpenseCategoryId(): ?int { return $this->expenseCategoryId; }
    public function getName(): string { return $this->name; }
    public function getPeriodStart(): \DateTimeInterface { return $this->periodStart; }
    public function getPeriodEnd(): \DateTimeInterface { return $this->periodEnd; }
    public function getAmount(): float { return $this->amount; }
    public function getSpentAmount(): float { return $this->spentAmount; }
    public function getCurrency(): string { return $this->currency; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function getRemainingAmount(): float { return $this->amount - $this->spentAmount; }
    public function getUtilizationPercent(): float { return $this->amount > 0 ? ($this->spentAmount / $this->amount) * 100 : 0.0; }
    public function isOverBudget(): bool { return $this->spentAmount > $this->amount; }
    public function recordSpend(float $amount): void { $this->spentAmount += $amount; }
}
