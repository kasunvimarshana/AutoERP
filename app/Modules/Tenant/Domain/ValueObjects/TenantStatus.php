<?php
namespace Modules\Tenant\Domain\ValueObjects;

class TenantStatus
{
    public const ACTIVE    = 'active';
    public const INACTIVE  = 'inactive';
    public const SUSPENDED = 'suspended';
    public const TRIAL     = 'trial';

    private static array $valid = [self::ACTIVE, self::INACTIVE, self::SUSPENDED, self::TRIAL];

    private function __construct(public readonly string $value) {}

    public static function from(string $value): self
    {
        if (!in_array($value, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid tenant status: {$value}");
        }
        return new self($value);
    }

    public static function valid(): array { return self::$valid; }

    public function __toString(): string { return $this->value; }
}
