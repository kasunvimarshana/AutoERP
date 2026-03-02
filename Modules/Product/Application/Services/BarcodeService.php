<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

/**
 * Barcode generation service.
 *
 * Inspired by the Barcode model in the PHP_POS reference repository (app/Barcode.php).
 * Generates EAN-13, Code128, and QR barcode data without requiring external libraries.
 */
class BarcodeService
{
    private const SUPPORTED_TYPES = ['EAN13', 'CODE128', 'QR'];

    /**
     * Generate a barcode value for a product SKU.
     *
     * @param string $sku       The product SKU or existing barcode value
     * @param string $type      Barcode type: EAN13 | CODE128 | QR
     * @return array{type: string, value: string, display: string}
     */
    public function generate(string $sku, string $type = 'CODE128'): array
    {
        $type = strtoupper($type);

        if (! in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException(
                'Unsupported barcode type. Supported: ' . implode(', ', self::SUPPORTED_TYPES)
            );
        }

        $value = match ($type) {
            'EAN13'   => $this->toEan13($sku),
            'CODE128' => $sku,
            'QR'      => $sku,
        };

        return [
            'type'    => $type,
            'value'   => $value,
            'display' => $this->formatDisplay($value, $type),
        ];
    }

    /**
     * Validate a barcode value for a given type.
     */
    public function validate(string $value, string $type): bool
    {
        return match (strtoupper($type)) {
            'EAN13'   => $this->validateEan13($value),
            'CODE128' => strlen($value) > 0 && strlen($value) <= 128,
            'QR'      => strlen($value) > 0,
            default   => false,
        };
    }

    /**
     * Convert a SKU string to a valid 13-digit EAN-13 code.
     * Uses the numeric hash of the SKU, then appends a check digit.
     */
    private function toEan13(string $sku): string
    {
        $digits = preg_replace('/\D/', '', $sku);

        if (strlen($digits) >= 12) {
            $base = substr($digits, 0, 12);
        } else {
            $base = str_pad($digits, 12, '0', STR_PAD_LEFT);
        }

        return $base . $this->ean13CheckDigit($base);
    }

    /**
     * Calculate the EAN-13 check digit for a 12-digit string.
     */
    private function ean13CheckDigit(string $digits12): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits12[$i] * (($i % 2 === 0) ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;
        return (string) $check;
    }

    private function validateEan13(string $value): bool
    {
        if (! preg_match('/^\d{13}$/', $value)) {
            return false;
        }
        $expected = $this->ean13CheckDigit(substr($value, 0, 12));
        return $value[12] === $expected;
    }

    private function formatDisplay(string $value, string $type): string
    {
        return match ($type) {
            'EAN13' => implode('-', [
                substr($value, 0, 1),
                substr($value, 1, 6),
                substr($value, 7, 6),
            ]),
            default => $value,
        };
    }
}
