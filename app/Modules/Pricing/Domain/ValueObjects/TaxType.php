<?php
namespace Modules\Pricing\Domain\ValueObjects;

class TaxType
{
    public const PERCENTAGE = 'percentage';
    public const FIXED = 'fixed';

    private static array $valid = [self::PERCENTAGE, self::FIXED];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid TaxType: {$value}");
        }
    }

    public static function from(string $value): self { return new self($value); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
