<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;

class BankTransaction
{
    // type: debit|credit
    // status: pending|categorized|reconciled|excluded
    // source: manual|import|api
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $bankAccountId,
        private \DateTimeInterface $transactionDate,
        private float $amount,
        private string $description,
        private string $type,
        private string $status,
        private ?int $expenseCategoryId,
        private ?int $accountId,
        private ?int $journalEntryId,
        private ?string $reference,
        private string $source,
        private array $metadata,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getBankAccountId(): int { return $this->bankAccountId; }
    public function getTransactionDate(): \DateTimeInterface { return $this->transactionDate; }
    public function getAmount(): float { return $this->amount; }
    public function getDescription(): string { return $this->description; }
    public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; }
    public function getExpenseCategoryId(): ?int { return $this->expenseCategoryId; }
    public function getAccountId(): ?int { return $this->accountId; }
    public function getJournalEntryId(): ?int { return $this->journalEntryId; }
    public function getReference(): ?string { return $this->reference; }
    public function getSource(): string { return $this->source; }
    public function getMetadata(): array { return $this->metadata; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isPending(): bool { return $this->status === 'pending'; }
    public function isDebit(): bool { return $this->type === 'debit'; }
    public function isCredit(): bool { return $this->type === 'credit'; }
    public function categorize(int $categoryId, int $accountId): void {
        $this->expenseCategoryId = $categoryId;
        $this->accountId = $accountId;
        $this->status = 'categorized';
    }
    public function reconcile(): void { $this->status = 'reconciled'; }
    public function exclude(): void { $this->status = 'excluded'; }
    public function reclassify(int $categoryId, int $accountId): void {
        $this->expenseCategoryId = $categoryId;
        $this->accountId = $accountId;
    }
}
