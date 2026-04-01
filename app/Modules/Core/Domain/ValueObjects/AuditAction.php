<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

/**
 * Represents the type of action that triggered an audit record.
 *
 * Using a final class with constants and a factory method rather than a native
 * PHP enum keeps the design compatible with the existing ValueObject pattern
 * and allows consumers to register custom action strings at runtime.
 */
final class AuditAction
{
    public const CREATED  = 'created';
    public const UPDATED  = 'updated';
    public const DELETED  = 'deleted';
    public const RESTORED = 'restored';
    public const CUSTOM   = 'custom';

    /**
     * The set of built-in actions.  Additional actions may be registered via
     * {@see self::register()} so that modules can extend the vocabulary.
     *
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

    /**
     * Create an AuditAction from a string value.
     *
     * @throws \InvalidArgumentException When the value is not a known action.
     */
    public static function from(string $value): self
    {
        if (! in_array($value, self::$registered, true)) {
            throw new \InvalidArgumentException(
                "Unknown audit action [{$value}]. Register it first with AuditAction::register()."
            );
        }

        return new self($value);
    }

    /**
     * Create an AuditAction without validation.  Useful when persisting a
     * value that was previously stored in the database.
     */
    public static function fromDatabase(string $value): self
    {
        return new self($value);
    }

    /**
     * Register a custom action string so it can be used with {@see self::from()}.
     */
    public static function register(string $action): void
    {
        if (! in_array($action, self::$registered, true)) {
            self::$registered[] = $action;
        }
    }

    /**
     * Return all currently registered action strings.
     *
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
