<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class AccountNature extends ValueObject
{
    public const DEBIT  = 'debit';
    public const CREDIT = 'credit';

    public const ALL = [self::DEBIT, self::CREDIT];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::ALL, true)) {
            throw new InvalidArgumentException(
                "Invalid account nature '{$value}'. Allowed: " . implode(', ', self::ALL)
            );
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isDebit(): bool
    {
        return $this->value === self::DEBIT;
    }

    public function isCredit(): bool
    {
        return $this->value === self::CREDIT;
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
