<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

use Modules\Finance\Domain\ValueObjects\AccountNature;
use Modules\Finance\Domain\ValueObjects\AccountType;

final class Account
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $uuid,
        public readonly int     $tenantId,
        public readonly string  $code,
        public readonly string  $name,
        public readonly AccountType   $type,
        public readonly AccountNature $nature,
        public readonly float   $openingBalance,
        public readonly float   $currentBalance,
        public readonly bool    $isActive,
        public readonly bool    $isBankAccount,
        public readonly bool    $isSystem,
        public readonly string  $currency,
        public readonly ?int    $parentId       = null,
        public readonly ?string $classification = null,
        public readonly ?string $description    = null,
        public readonly ?string $bankName       = null,
        public readonly ?string $bankAccountNumber  = null,
        public readonly ?string $bankRoutingNumber  = null,
        public readonly ?array  $metadata       = null,
    ) {}

    /**
     * Whether this account's normal balance is on the debit side.
     */
    public function normalBalanceIsDebit(): bool
    {
        return $this->nature->isDebit();
    }

    /**
     * Calculate the net effect of a debit on this account's balance.
     * Debit increases debit-normal accounts; decreases credit-normal accounts.
     */
    public function applyDebit(float $amount): float
    {
        return $this->normalBalanceIsDebit()
            ? $this->currentBalance + $amount
            : $this->currentBalance - $amount;
    }

    /**
     * Calculate the net effect of a credit on this account's balance.
     * Credit increases credit-normal accounts; decreases debit-normal accounts.
     */
    public function applyCredit(float $amount): float
    {
        return $this->normalBalanceIsDebit()
            ? $this->currentBalance - $amount
            : $this->currentBalance + $amount;
    }
}
