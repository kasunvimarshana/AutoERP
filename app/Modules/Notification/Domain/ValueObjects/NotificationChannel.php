<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObjects;

/**
 * Supported notification delivery channels.
 */
class NotificationChannel
{
    public const DATABASE = 'database';
    public const EMAIL    = 'email';
    public const SMS      = 'sms';
    public const PUSH     = 'push';

    private static array $valid = [
        self::DATABASE,
        self::EMAIL,
        self::SMS,
        self::PUSH,
    ];

    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        if (!in_array($value, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid notification channel: {$value}");
        }

        return new self($value);
    }

    public static function database(): self { return new self(self::DATABASE); }
    public static function email(): self    { return new self(self::EMAIL); }
    public static function sms(): self      { return new self(self::SMS); }
    public static function push(): self     { return new self(self::PUSH); }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function all(): array
    {
        return self::$valid;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
