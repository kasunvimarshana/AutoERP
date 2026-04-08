<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

use Modules\Finance\Domain\ValueObjects\TransactionType;

final class Transaction
{
    public function __construct(
        public readonly int             $id,
        public readonly string          $uuid,
        public readonly int             $tenantId,
        public readonly string          $referenceNumber,
        public readonly TransactionType $type,
        public readonly string          $status,
        public readonly string          $transactionDate,
        public readonly float           $amount,
        public readonly string          $currency,
        public readonly float           $exchangeRate,
        public readonly ?int            $journalEntryId = null,
        public readonly ?int            $fromAccountId  = null,
        public readonly ?int            $toAccountId    = null,
        public readonly ?string         $description    = null,
        public readonly ?string         $category       = null,
        public readonly ?array          $tags           = null,
        public readonly ?string         $contactType    = null,
        public readonly ?int            $contactId      = null,
        public readonly ?array          $attachments    = null,
        public readonly ?array          $metadata       = null,
    ) {}

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Amount converted to base currency using exchange rate.
     */
    public function baseCurrencyAmount(): float
    {
        return $this->amount * $this->exchangeRate;
    }
}
