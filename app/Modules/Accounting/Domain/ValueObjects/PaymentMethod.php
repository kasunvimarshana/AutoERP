<?php
namespace Modules\Accounting\Domain\ValueObjects;

class PaymentMethod
{
    public const CASH = 'cash';
    public const BANK_TRANSFER = 'bank_transfer';
    public const CARD = 'card';
    public const CHEQUE = 'cheque';
    public const CREDIT = 'credit';
    public const OTHER = 'other';

    private static array $valid = [self::CASH, self::BANK_TRANSFER, self::CARD, self::CHEQUE, self::CREDIT, self::OTHER];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid PaymentMethod: {$value}");
        }
    }

    public static function from(string $v): self { return new self($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
