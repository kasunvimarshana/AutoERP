<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\ValueObjects;

final class AuditAction
{
    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const DELETED = 'deleted';

    public const RESTORED = 'restored';

    public const CUSTOM = 'custom';

    /**
     * @var array<string>
     */
    private static array $registered = [
        self::CREATED,
        self::UPDATED,
        self::DELETED,
        self::RESTORED,
        self::CUSTOM,
    ];

    private function __construct(private readonly string $value) {}

    public static function from(string $value): self
    {
        if (! in_array($value, self::$registered, true)) {
            throw new \InvalidArgumentException(
                "Unknown audit action [{$value}]. Register it first with AuditAction::register()."
            );
        }

        return new self($value);
    }

    public static function fromDatabase(string $value): self
    {
        return new self($value);
    }

    public static function register(string $action): void
    {
        if (! in_array($action, self::$registered, true)) {
            self::$registered[] = $action;
        }
    }

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        return self::$registered;
    }

    public function value(): string
    {
        return $this->value;
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
