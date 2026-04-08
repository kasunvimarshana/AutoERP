<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class AccountType extends ValueObject
{
    public const ASSET     = 'asset';
    public const LIABILITY = 'liability';
    public const EQUITY    = 'equity';
    public const REVENUE   = 'revenue';
    public const EXPENSE   = 'expense';

    public const ALL = [
        self::ASSET,
        self::LIABILITY,
        self::EQUITY,
        self::REVENUE,
        self::EXPENSE,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::ALL, true)) {
            throw new InvalidArgumentException(
                "Invalid account type '{$value}'. Allowed: " . implode(', ', self::ALL)
            );
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Asset and Expense accounts have a debit normal balance.
     */
    public function isDebitNormal(): bool
    {
        return in_array($this->value, [self::ASSET, self::EXPENSE], true);
    }

    /**
     * Liability, Equity and Revenue accounts have a credit normal balance.
     */
    public function isCreditNormal(): bool
    {
        return ! $this->isDebitNormal();
    }

    /**
     * Return the default AccountNature for this account type.
     */
    public function defaultNature(): AccountNature
    {
        return $this->isDebitNormal()
            ? new AccountNature(AccountNature::DEBIT)
            : new AccountNature(AccountNature::CREDIT);
    }

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public static function fromArray(array $data): static
    {
        return new static($data['value']);
    }
}
