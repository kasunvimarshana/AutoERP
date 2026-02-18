<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

enum AccountType: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';

    /**
     * Get a human-readable label for the account type.
     */
    public function label(): string
    {
        return match ($this) {
            self::ASSET => 'Asset',
            self::LIABILITY => 'Liability',
            self::EQUITY => 'Equity',
            self::REVENUE => 'Revenue',
            self::EXPENSE => 'Expense',
        };
    }

    /**
     * Check if the account type has a normal debit balance.
     */
    public function hasDebitBalance(): bool
    {
        return in_array($this, [self::ASSET, self::EXPENSE]);
    }

    /**
     * Check if the account type has a normal credit balance.
     */
    public function hasCreditBalance(): bool
    {
        return in_array($this, [self::LIABILITY, self::EQUITY, self::REVENUE]);
    }
}
