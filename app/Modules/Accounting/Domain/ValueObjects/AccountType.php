<?php
namespace Modules\Accounting\Domain\ValueObjects;

class AccountType
{
    public const ASSET = 'asset';
    public const LIABILITY = 'liability';
    public const EQUITY = 'equity';
    public const REVENUE = 'revenue';
    public const EXPENSE = 'expense';

    private static array $valid = [self::ASSET, self::LIABILITY, self::EQUITY, self::REVENUE, self::EXPENSE];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid AccountType: {$value}");
        }
    }

    public static function from(string $v): self { return new self($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
