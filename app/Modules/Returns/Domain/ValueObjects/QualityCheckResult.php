<?php

namespace Modules\Returns\Domain\ValueObjects;

class QualityCheckResult
{
    const PASS = 'pass';
    const FAIL = 'fail';
    const PENDING = 'pending';

    private static array $valid = [
        self::PASS,
        self::FAIL,
        self::PENDING,
    ];

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function from(string $value): self
    {
        if (!self::valid($value)) {
            throw new \InvalidArgumentException("Invalid quality check result: {$value}");
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
