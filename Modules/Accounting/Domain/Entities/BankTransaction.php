<?php

namespace Modules\Accounting\Domain\Entities;

use Modules\Accounting\Domain\Enums\BankTransactionType;
use Modules\Accounting\Domain\Enums\BankTransactionStatus;

class BankTransaction
{
    public function __construct(
        private readonly string                $id,
        private readonly string                $tenantId,
        private readonly string                $bankAccountId,
        private readonly BankTransactionType   $type,
        private readonly string                $amount,
        private readonly string                $transactionDate,
        private readonly string                $description,
        private readonly BankTransactionStatus $status,
        private readonly ?string               $referenceNumber,
        private readonly ?string               $journalEntryId,
    ) {}

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getBankAccountId(): string { return $this->bankAccountId; }
    public function getType(): BankTransactionType { return $this->type; }
    public function getAmount(): string { return $this->amount; }
    public function getTransactionDate(): string { return $this->transactionDate; }
    public function getDescription(): string { return $this->description; }
    public function getStatus(): BankTransactionStatus { return $this->status; }
    public function getReferenceNumber(): ?string { return $this->referenceNumber; }
    public function getJournalEntryId(): ?string { return $this->journalEntryId; }
}
