<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

interface Gs1BarcodeServiceInterface
{
    /**
     * Parse a raw barcode string and return structured GS1 data.
     *
     * @return array{type: string, gtin: string, gs1_company_prefix: string|null, payload: string}
     */
    public function parse(string $barcode): array;

    /**
     * Validate that the given GTIN has a correct GS1 check digit.
     */
    public function validateCheckDigit(string $gtin): bool;

    /**
     * Calculate the GS1 check digit for a GTIN (without the check digit).
     */
    public function calculateCheckDigit(string $gtinWithoutCheck): string;

    /**
     * Determine the GTIN type from the digit count (GTIN-8, -12, -13, or -14).
     */
    public function detectGtinType(string $gtin): string;

    /**
     * Look up a product by its GTIN.
     */
    public function findByGtin(int $tenantId, string $gtin): mixed;
}
