<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

interface UomConversionServiceInterface
{
    /**
     * Convert a quantity from one UOM to another.
     * Throws InvalidArgumentException if no conversion rule is found.
     */
    public function convert(
        int $tenantId,
        float $quantity,
        string $fromUom,
        string $toUom,
        ?string $productId = null,
    ): float;

    /**
     * Create or update a UOM conversion rule.
     */
    public function upsertConversion(array $data): mixed;

    /**
     * List all active conversions for a UOM within a tenant.
     */
    public function listByUom(int $tenantId, string $uom): \Illuminate\Support\Collection;
}
