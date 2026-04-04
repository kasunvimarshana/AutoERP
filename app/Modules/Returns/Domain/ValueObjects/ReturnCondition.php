<?php

namespace Modules\Returns\Domain\ValueObjects;

class ReturnCondition
{
    const GOOD = 'good';
    const DAMAGED = 'damaged';
    const EXPIRED = 'expired';
    const FAULTY = 'faulty';

    private static array $valid = [
        self::GOOD,
        self::DAMAGED,
        self::EXPIRED,
        self::FAULTY,
    ];

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function from(string $value): self
    {
        if (!self::valid($value)) {
            throw new \InvalidArgumentException("Invalid return condition: {$value}");
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
