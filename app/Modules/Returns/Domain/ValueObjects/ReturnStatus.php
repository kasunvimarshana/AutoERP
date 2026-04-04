<?php

namespace Modules\Returns\Domain\ValueObjects;

class ReturnStatus
{
    const DRAFT = 'draft';
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const IN_PROCESS = 'in_process';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';

    private static array $valid = [
        self::DRAFT,
        self::PENDING,
        self::APPROVED,
        self::IN_PROCESS,
        self::COMPLETED,
        self::CANCELLED,
    ];

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function from(string $value): self
    {
        if (!self::valid($value)) {
            throw new \InvalidArgumentException("Invalid return status: {$value}");
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
