<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

class JournalEntryLine
{
    public function __construct(
        private ?int $id,
        private ?int $journalEntryId,
        private int $accountId,
        private float $debitAmount,
        private float $creditAmount,
        private ?string $description,
        private ?string $referenceLine,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getJournalEntryId(): ?int { return $this->journalEntryId; }
    public function getAccountId(): int { return $this->accountId; }
    public function getDebitAmount(): float { return $this->debitAmount; }
    public function getCreditAmount(): float { return $this->creditAmount; }
    public function getDescription(): ?string { return $this->description; }
    public function getReferenceLine(): ?string { return $this->referenceLine; }
}
