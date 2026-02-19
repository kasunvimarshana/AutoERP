<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

/**
 * Account Type Enum
 *
 * Defines the five fundamental account types in double-entry bookkeeping
 */
enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    /**
     * Get normal balance for account type
     * Assets, Expenses = Debit
     * Liabilities, Equity, Revenue = Credit
     */
    public function normalBalance(): string
    {
        return match ($this) {
            self::Asset, self::Expense => 'debit',
            self::Liability, self::Equity, self::Revenue => 'credit',
        };
    }

    /**
     * Get all debit account types
     */
    public static function debitTypes(): array
    {
        return [self::Asset, self::Expense];
    }

    /**
     * Get all credit account types
     */
    public static function creditTypes(): array
    {
        return [self::Liability, self::Equity, self::Revenue];
    }
}
