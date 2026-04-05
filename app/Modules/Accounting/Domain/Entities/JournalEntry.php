<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;
class JournalEntry {
    public function __construct(
        private ?int $id, private int $tenantId, private string $entryNumber,
        private string $status, // draft|posted|reversed
        private string $description, private string $currency, private float $totalDebit,
        private float $totalCredit, private ?string $reference, private ?int $createdBy,
        private array $lines,
        private ?\DateTimeInterface $postedAt, private ?\DateTimeInterface $createdAt, private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getEntryNumber(): string { return $this->entryNumber; }
    public function getStatus(): string { return $this->status; }
    public function getDescription(): string { return $this->description; }
    public function getCurrency(): string { return $this->currency; }
    public function getTotalDebit(): float { return $this->totalDebit; }
    public function getTotalCredit(): float { return $this->totalCredit; }
    public function getReference(): ?string { return $this->reference; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function getLines(): array { return $this->lines; }
    public function getPostedAt(): ?\DateTimeInterface { return $this->postedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isBalanced(): bool { return abs($this->totalDebit - $this->totalCredit) < 0.0001; }
    public function post(): void {
        if ($this->status !== 'draft') throw new \DomainException("Only draft entries can be posted.");
        if (!$this->isBalanced()) throw new \DomainException("Journal entry is not balanced. Debit: {$this->totalDebit}, Credit: {$this->totalCredit}");
        $this->status = 'posted'; $this->postedAt = new \DateTimeImmutable();
    }
}
