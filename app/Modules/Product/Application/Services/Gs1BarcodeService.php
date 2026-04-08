<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use InvalidArgumentException;
use Modules\Product\Application\Contracts\Gs1BarcodeServiceInterface;
use Modules\Product\Domain\Contracts\Repositories\ProductRepositoryInterface;

/**
 * GS1 Barcode Service.
 *
 * Supports GTIN-8 (EAN-8), GTIN-12 (UPC-A), GTIN-13 (EAN-13), GTIN-14 (ITF-14/SCC-14).
 * Implements GS1 check-digit algorithm (Luhn-like modulo 10).
 */
class Gs1BarcodeService implements Gs1BarcodeServiceInterface
{
    private const VALID_GTIN_LENGTHS = [8, 12, 13, 14];

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    /**
     * Parse a raw barcode string and return structured GS1 data.
     *
     * @return array{type: string, gtin: string, gs1_company_prefix: string|null, payload: string}
     */
    public function parse(string $barcode): array
    {
        $barcode = trim($barcode);

        if (! ctype_digit($barcode)) {
            throw new InvalidArgumentException("Barcode must contain only digits: {$barcode}");
        }

        $len  = strlen($barcode);
        $type = $this->detectGtinType($barcode);

        if (! $this->validateCheckDigit($barcode)) {
            throw new InvalidArgumentException("Invalid GS1 check digit for barcode: {$barcode}");
        }

        return [
            'type'               => $type,
            'gtin'               => $barcode,
            'gs1_company_prefix' => $this->extractCompanyPrefix($barcode),
            'payload'            => $barcode,
        ];
    }

    /**
     * Validate that the given GTIN has a correct GS1 check digit.
     */
    public function validateCheckDigit(string $gtin): bool
    {
        $len = strlen($gtin);
        if (! in_array($len, self::VALID_GTIN_LENGTHS, true)) {
            return false;
        }

        $withoutCheck = substr($gtin, 0, $len - 1);
        $expected     = $this->calculateCheckDigit($withoutCheck);

        return $expected === substr($gtin, -1);
    }

    /**
     * Calculate the GS1 check digit for a GTIN (without the check digit).
     *
     * Algorithm: starting from the rightmost digit (of the payload without check),
     * alternate multiplying by 3 and 1, sum all products, check digit = (10 - (sum % 10)) % 10.
     */
    public function calculateCheckDigit(string $gtinWithoutCheck): string
    {
        $digits  = str_split(strrev($gtinWithoutCheck));
        $sum     = 0;
        foreach ($digits as $i => $digit) {
            $multiplier = ($i % 2 === 0) ? 3 : 1;
            $sum        += (int) $digit * $multiplier;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    /**
     * Determine the GTIN type from the digit count.
     */
    public function detectGtinType(string $gtin): string
    {
        return match (strlen($gtin)) {
            8  => 'GTIN-8',
            12 => 'GTIN-12',
            13 => 'GTIN-13',
            14 => 'GTIN-14',
            default => throw new InvalidArgumentException(
                'Barcode length must be 8, 12, 13, or 14 digits. Got: '.strlen($gtin)
            ),
        };
    }

    /**
     * Look up a product by its GTIN.
     */
    public function findByGtin(int $tenantId, string $gtin): mixed
    {
        return $this->productRepository->findByGtin($tenantId, $gtin);
    }

    /**
     * Extract GS1 company prefix heuristic (first 7–10 digits, excluding check digit).
     * Returns null for GTIN-8 as it follows a different structure.
     */
    private function extractCompanyPrefix(string $gtin): ?string
    {
        $len = strlen($gtin);
        if ($len === 8) {
            return null;
        }

        // Heuristic: first 7 digits after the leading indicator digit (for GTIN-14)
        // or first 7 digits for GTIN-12/13. Actual prefix length varies by company.
        return substr($gtin, 0, 7);
    }
}
