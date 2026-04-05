<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\ValueObjects;

/**
 * Supported barcode symbology types.
 */
class BarcodeType
{
    // ── 1-D linear symbologies ────────────────────────────────────────────────
    public const CODE128        = 'CODE128';
    public const CODE39         = 'CODE39';
    public const CODE93         = 'CODE93';
    public const EAN13          = 'EAN13';
    public const EAN8           = 'EAN8';
    public const UPCA           = 'UPCA';
    public const UPCE           = 'UPCE';
    public const ITF14          = 'ITF14';
    public const CODABAR        = 'CODABAR';
    public const MSI            = 'MSI';
    public const INTERLEAVED2OF5 = 'INTERLEAVED2OF5';

    // ── 2-D matrix symbologies ────────────────────────────────────────────────
    public const QR         = 'QR';
    public const DATAMATRIX = 'DATAMATRIX';
    public const PDF417     = 'PDF417';
    public const AZTEC      = 'AZTEC';

    private const ONE_DIMENSIONAL = [
        self::CODE128,
        self::CODE39,
        self::CODE93,
        self::EAN13,
        self::EAN8,
        self::UPCA,
        self::UPCE,
        self::ITF14,
        self::CODABAR,
        self::MSI,
        self::INTERLEAVED2OF5,
    ];

    private const TWO_DIMENSIONAL = [
        self::QR,
        self::DATAMATRIX,
        self::PDF417,
        self::AZTEC,
    ];

    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        if (!in_array($value, array_merge(self::ONE_DIMENSIONAL, self::TWO_DIMENSIONAL), true)) {
            throw new \InvalidArgumentException("Unknown barcode type: {$value}");
        }

        return new self($value);
    }

    // ── Named constructors ────────────────────────────────────────────────────

    public static function code128(): self        { return new self(self::CODE128); }
    public static function code39(): self         { return new self(self::CODE39); }
    public static function code93(): self         { return new self(self::CODE93); }
    public static function ean13(): self          { return new self(self::EAN13); }
    public static function ean8(): self           { return new self(self::EAN8); }
    public static function upcA(): self           { return new self(self::UPCA); }
    public static function upcE(): self           { return new self(self::UPCE); }
    public static function itf14(): self          { return new self(self::ITF14); }
    public static function codabar(): self        { return new self(self::CODABAR); }
    public static function msi(): self            { return new self(self::MSI); }
    public static function interleaved2of5(): self { return new self(self::INTERLEAVED2OF5); }
    public static function qr(): self             { return new self(self::QR); }
    public static function dataMatrix(): self     { return new self(self::DATAMATRIX); }
    public static function pdf417(): self         { return new self(self::PDF417); }
    public static function aztec(): self          { return new self(self::AZTEC); }

    // ── Queries ───────────────────────────────────────────────────────────────

    public function getValue(): string
    {
        return $this->value;
    }

    public function isOneDimensional(): bool
    {
        return in_array($this->value, self::ONE_DIMENSIONAL, true);
    }

    public function isTwoDimensional(): bool
    {
        return in_array($this->value, self::TWO_DIMENSIONAL, true);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public static function allTypes(): array
    {
        return array_merge(self::ONE_DIMENSIONAL, self::TWO_DIMENSIONAL);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
