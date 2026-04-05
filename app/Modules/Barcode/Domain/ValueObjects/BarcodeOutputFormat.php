<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\ValueObjects;

/**
 * Supported output formats for rendered barcodes.
 */
class BarcodeOutputFormat
{
    public const SVG        = 'svg';
    public const PNG_BASE64 = 'png_base64';
    public const RAW        = 'raw';

    private static array $valid = [
        self::SVG,
        self::PNG_BASE64,
        self::RAW,
    ];

    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        if (!in_array($value, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid barcode output format: {$value}");
        }

        return new self($value);
    }

    public static function svg(): self       { return new self(self::SVG); }
    public static function pngBase64(): self { return new self(self::PNG_BASE64); }
    public static function raw(): self       { return new self(self::RAW); }

    public function getValue(): string
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
