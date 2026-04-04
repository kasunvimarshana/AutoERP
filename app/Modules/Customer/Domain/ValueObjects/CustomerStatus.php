<?php
namespace Modules\Customer\Domain\ValueObjects;

final class CustomerStatus
{
    public const ACTIVE    = 'active';
    public const INACTIVE  = 'inactive';
    public const SUSPENDED = 'suspended';

    private static array $valid = [self::ACTIVE, self::INACTIVE, self::SUSPENDED];

    public function __construct(private readonly string $value)
    {
        if (!self::valid($this->value)) {
            throw new \InvalidArgumentException("Invalid CustomerStatus: {$this->value}");
        }
    }

    public static function from(string $value): self { return new self($value); }
    public static function valid(string $value): bool { return in_array($value, self::$valid, true); }
    public function __toString(): string { return $this->value; }
}
