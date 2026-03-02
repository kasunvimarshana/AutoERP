<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;
use DateTimeImmutable;
class JournalEntry {
    private array $lines = [];
    public function __construct(
        private readonly int             $id,
        private readonly int             $tenantId,
        private string                   $entryNumber,
        private DateTimeImmutable        $entryDate,
        private string                   $description,
        private ?string                  $referenceType,
        private ?int                     $referenceId,
        private bool                     $isPosted,
        private bool                     $isReversed,
        array                            $lines = [],
    ) {
        $this->lines = $lines;
    }
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getEntryNumber(): string { return $this->entryNumber; }
    public function getEntryDate(): DateTimeImmutable { return $this->entryDate; }
    public function getDescription(): string { return $this->description; }
    public function getReferenceType(): ?string { return $this->referenceType; }
    public function getReferenceId(): ?int { return $this->referenceId; }
    public function isPosted(): bool { return $this->isPosted; }
    public function isReversed(): bool { return $this->isReversed; }
    public function getLines(): array { return $this->lines; }
    public function validate(): void {
        if (empty($this->lines)) throw new \DomainException('Journal entry must have at least one line.');
        $totalDebits  = '0.0000';
        $totalCredits = '0.0000';
        foreach ($this->lines as $line) {
            $totalDebits  = bcadd($totalDebits, $line->getDebitAmount(), 4);
            $totalCredits = bcadd($totalCredits, $line->getCreditAmount(), 4);
        }
        if (bccomp($totalDebits, $totalCredits, 4) !== 0) {
            throw new \DomainException("Journal entry is unbalanced. Debits: {$totalDebits}, Credits: {$totalCredits}.");
        }
    }
    public function post(): void {
        $this->validate();
        if ($this->isPosted) throw new \DomainException('Journal entry is already posted.');
        $this->isPosted = true;
    }
}
