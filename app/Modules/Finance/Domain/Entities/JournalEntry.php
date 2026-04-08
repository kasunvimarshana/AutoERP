<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

use Modules\Finance\Domain\ValueObjects\EntryStatus;

final class JournalEntry
{
    public function __construct(
        public readonly int         $id,
        public readonly string      $uuid,
        public readonly int         $tenantId,
        public readonly string      $referenceNumber,
        public readonly string      $entryDate,
        public readonly EntryStatus $status,
        public readonly float       $totalDebit,
        public readonly float       $totalCredit,
        public readonly string      $currency,
        public readonly ?string     $description  = null,
        public readonly ?string     $postedAt     = null,
        public readonly ?int        $postedBy     = null,
        public readonly ?string     $voidedAt     = null,
        public readonly ?int        $voidedBy     = null,
        public readonly ?string     $voidReason   = null,
        public readonly ?string     $sourceType   = null,
        public readonly ?int        $sourceId     = null,
        public readonly ?array      $metadata     = null,
    ) {}

    /**
     * Check whether debit and credit totals are balanced.
     */
    public function isBalanced(): bool
    {
        return abs($this->totalDebit - $this->totalCredit) < 0.000001;
    }

    public function isDraft(): bool
    {
        return $this->status->isDraft();
    }

    public function isPosted(): bool
    {
        return $this->status->isPosted();
    }

    public function isVoided(): bool
    {
        return $this->status->isVoided();
    }
}
