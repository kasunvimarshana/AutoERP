<?php
namespace Modules\User\Domain\ValueObjects;

class UserStatus
{
    public const ACTIVE   = 'active';
    public const INACTIVE = 'inactive';
    public const BANNED   = 'banned';

    private static array $valid = [self::ACTIVE, self::INACTIVE, self::BANNED];

    private function __construct(public readonly string $value) {}

    public static function from(string $value): self
    {
        if (!in_array($value, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid user status: {$value}");
        }
        return new self($value);
    }

    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
