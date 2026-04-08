<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class TransactionType extends ValueObject
{
    public const INCOME     = 'income';
    public const EXPENSE    = 'expense';
    public const TRANSFER   = 'transfer';
    public const PAYMENT    = 'payment';
    public const REFUND     = 'refund';
    public const ADJUSTMENT = 'adjustment';

    public const ALL = [
        self::INCOME,
        self::EXPENSE,
        self::TRANSFER,
        self::PAYMENT,
        self::REFUND,
        self::ADJUSTMENT,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::ALL, true)) {
            throw new InvalidArgumentException(
                "Invalid transaction type '{$value}'. Allowed: " . implode(', ', self::ALL)
            );
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
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
