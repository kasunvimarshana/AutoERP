<?php

namespace Modules\Returns\Domain\ValueObjects;

class ReturnType
{
    const PURCHASE_RETURN = 'purchase_return';
    const SALES_RETURN = 'sales_return';

    private static array $valid = [
        self::PURCHASE_RETURN,
        self::SALES_RETURN,
    ];

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function from(string $value): self
    {
        if (!self::valid($value)) {
            throw new \InvalidArgumentException("Invalid return type: {$value}");
        }

        return new self($value);
    }

    public static function valid(string $value): bool
    {
        return in_array($value, self::$valid, true);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
