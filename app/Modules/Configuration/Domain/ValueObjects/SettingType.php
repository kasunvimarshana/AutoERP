<?php
namespace Modules\Configuration\Domain\ValueObjects;

final class SettingType
{
    public const STRING  = 'string';
    public const INTEGER = 'integer';
    public const FLOAT   = 'float';
    public const BOOLEAN = 'boolean';
    public const JSON    = 'json';
    public const TEXT    = 'text';

    private static array $valid = [self::STRING, self::INTEGER, self::FLOAT, self::BOOLEAN, self::JSON, self::TEXT];

    public function __construct(private readonly string $value)
    {
        if (!self::valid($this->value)) {
            throw new \InvalidArgumentException("Invalid SettingType: {$this->value}");
        }
    }

    public static function from(string $value): self { return new self($value); }
    public static function valid(string $value): bool { return in_array($value, self::$valid, true); }
    public function __toString(): string { return $this->value; }
}
