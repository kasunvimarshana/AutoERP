<?php

namespace App\Modules\Finance\Enums;

enum AccountType: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';
    case CONTRA_ASSET = 'contra_asset';
    case CONTRA_LIABILITY = 'contra_liability';

    public function label(): string
    {
        return match ($this) {
            self::ASSET => 'Asset',
            self::LIABILITY => 'Liability',
            self::EQUITY => 'Equity',
            self::REVENUE => 'Revenue',
            self::EXPENSE => 'Expense',
            self::CONTRA_ASSET => 'Contra Asset',
            self::CONTRA_LIABILITY => 'Contra Liability',
        };
    }

    public function normalBalance(): string
    {
        return match ($this) {
            self::ASSET, self::EXPENSE, self::CONTRA_LIABILITY => 'debit',
            self::LIABILITY, self::EQUITY, self::REVENUE, self::CONTRA_ASSET => 'credit',
        };
    }

    public function isDebitNormal(): bool
    {
        return $this->normalBalance() === 'debit';
    }

    public function isCreditNormal(): bool
    {
        return $this->normalBalance() === 'credit';
    }
}
