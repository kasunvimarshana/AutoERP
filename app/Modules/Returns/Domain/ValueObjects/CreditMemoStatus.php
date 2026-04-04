<?php

namespace Modules\Returns\Domain\ValueObjects;

class CreditMemoStatus
{
    const DRAFT = 'draft';
    const ISSUED = 'issued';
    const APPLIED = 'applied';
    const VOIDED = 'voided';

    private static array $valid = [
        self::DRAFT,
        self::ISSUED,
        self::APPLIED,
        self::VOIDED,
    ];

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function from(string $value): self
    {
        if (!self::valid($value)) {
            throw new \InvalidArgumentException("Invalid credit memo status: {$value}");
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
