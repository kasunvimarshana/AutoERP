<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;
class JournalLine {
    public function __construct(
        private readonly int    $accountId,
        private readonly ?string $description,
        private readonly string $debitAmount,
        private readonly string $creditAmount,
    ) {
        $hasDebit  = bccomp($debitAmount, '0', 4) > 0;
        $hasCredit = bccomp($creditAmount, '0', 4) > 0;
        if ($hasDebit && $hasCredit) {
            throw new \DomainException('A journal line cannot have both a debit and credit amount.');
        }
        if (!$hasDebit && !$hasCredit) {
            throw new \DomainException('A journal line must have either a debit or credit amount.');
        }
    }
    public function getAccountId(): int { return $this->accountId; }
    public function getDescription(): ?string { return $this->description; }
    public function getDebitAmount(): string { return $this->debitAmount; }
    public function getCreditAmount(): string { return $this->creditAmount; }
    public function isDebit(): bool { return bccomp($this->debitAmount, '0', 4) > 0; }
    public function isCredit(): bool { return bccomp($this->creditAmount, '0', 4) > 0; }
    public function getAmount(): string {
        return $this->isDebit() ? $this->debitAmount : $this->creditAmount;
    }
}
