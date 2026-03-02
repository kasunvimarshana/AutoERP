<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Enums;
enum AccountType: string {
    case ASSET     = 'asset';
    case LIABILITY = 'liability';
    case EQUITY    = 'equity';
    case REVENUE   = 'revenue';
    case EXPENSE   = 'expense';
    public function normalBalance(): string {
        return match($this) {
            self::ASSET, self::EXPENSE   => 'debit',
            self::LIABILITY, self::EQUITY, self::REVENUE => 'credit',
        };
    }
    public function label(): string {
        return match($this) {
            self::ASSET     => 'Asset',
            self::LIABILITY => 'Liability',
            self::EQUITY    => 'Equity',
            self::REVENUE   => 'Revenue',
            self::EXPENSE   => 'Expense',
        };
    }
}
