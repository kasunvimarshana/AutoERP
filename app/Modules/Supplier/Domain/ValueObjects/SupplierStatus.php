<?php
namespace Modules\Supplier\Domain\ValueObjects;

final class SupplierStatus
{
    public const ACTIVE      = 'active';
    public const INACTIVE    = 'inactive';
    public const BLACKLISTED = 'blacklisted';

    private static array $valid = [self::ACTIVE, self::INACTIVE, self::BLACKLISTED];

    public function __construct(private readonly string $value)
    {
        if (!self::valid($this->value)) {
            throw new \InvalidArgumentException("Invalid SupplierStatus: {$this->value}");
        }
    }

    public static function from(string $value): self { return new self($value); }
    public static function valid(string $value): bool { return in_array($value, self::$valid, true); }
    public function __toString(): string { return $this->value; }
}
