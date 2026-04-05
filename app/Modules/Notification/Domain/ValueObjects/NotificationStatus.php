<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObjects;

/**
 * Lifecycle states for a notification record.
 */
class NotificationStatus
{
    public const PENDING  = 'pending';
    public const SENT     = 'sent';
    public const FAILED   = 'failed';
    public const READ     = 'read';

    private static array $valid = [
        self::PENDING,
        self::SENT,
        self::FAILED,
        self::READ,
    ];

    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        if (!in_array($value, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid notification status: {$value}");
        }

        return new self($value);
    }

    public static function pending(): self { return new self(self::PENDING); }
    public static function sent(): self    { return new self(self::SENT); }
    public static function failed(): self  { return new self(self::FAILED); }
    public static function read(): self    { return new self(self::READ); }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isPending(): bool  { return $this->value === self::PENDING; }
    public function isSent(): bool     { return $this->value === self::SENT; }
    public function isFailed(): bool   { return $this->value === self::FAILED; }
    public function isRead(): bool     { return $this->value === self::READ; }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
