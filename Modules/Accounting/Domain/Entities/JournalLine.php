<?php

namespace Modules\Accounting\Domain\Entities;

class JournalLine
{
    public function __construct(
        private readonly string  $id,
        private readonly string  $journalEntryId,
        private readonly string  $accountId,
        private readonly string  $debit,
        private readonly string  $credit,
        private readonly ?string $description,
    ) {}

    public function getId(): string { return $this->id; }
    public function getJournalEntryId(): string { return $this->journalEntryId; }
    public function getAccountId(): string { return $this->accountId; }
    public function getDebit(): string { return $this->debit; }
    public function getCredit(): string { return $this->credit; }
    public function getDescription(): ?string { return $this->description; }
}
