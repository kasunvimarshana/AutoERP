<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

/**
 * Account Type Enum
 *
 * Chart of accounts classification.
 * Based on standard accounting principles.
 *
 * @package Modules\Accounting\Enums
 */
enum AccountTypeEnum: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';

    /**
     * Get all account type values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     *
     * @return string
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
     * Get normal balance (debit or credit)
     *
     * @return string
     */
    public function normalBalance(): string
    {
        return match ($this) {
            self::ASSET, self::EXPENSE => 'debit',
            self::LIABILITY, self::EQUITY, self::REVENUE => 'credit',
        };
    }

    /**
     * Check if this is a balance sheet account
     *
     * @return bool
     */
    public function isBalanceSheet(): bool
    {
        return in_array($this, [
            self::ASSET,
            self::LIABILITY,
            self::EQUITY,
        ], true);
    }

    /**
     * Check if this is an income statement account
     *
     * @return bool
     */
    public function isIncomeStatement(): bool
    {
        return in_array($this, [
            self::REVENUE,
            self::EXPENSE,
        ], true);
    }

    /**
     * Get financial statement category
     *
     * @return string
     */
    public function financialStatement(): string
    {
        return match ($this) {
            self::ASSET, self::LIABILITY, self::EQUITY => 'Balance Sheet',
            self::REVENUE, self::EXPENSE => 'Income Statement',
        };
    }
}
