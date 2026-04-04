<?php
namespace Modules\GS1\Domain\ValueObjects;

final class BarcodeType
{
    public const GTIN_8  = 'GTIN-8';
    public const GTIN_12 = 'GTIN-12';
    public const GTIN_13 = 'GTIN-13';
    public const GTIN_14 = 'GTIN-14';
    public const SSCC    = 'SSCC';
    public const GS1_128 = 'GS1-128';

    private static array $valid = [
        self::GTIN_8, self::GTIN_12, self::GTIN_13, self::GTIN_14,
        self::SSCC, self::GS1_128,
    ];

    public function __construct(private readonly string $value)
    {
        if (!self::valid($this->value)) {
            throw new \InvalidArgumentException("Invalid BarcodeType: {$this->value}");
        }
    }

    public static function from(string $value): self { return new self($value); }
    public static function valid(string $value): bool { return in_array($value, self::$valid, true); }
    public function __toString(): string { return $this->value; }
}
