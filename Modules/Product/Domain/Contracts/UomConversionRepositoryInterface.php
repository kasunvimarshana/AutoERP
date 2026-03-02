<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Contracts;

use Modules\Product\Domain\Entities\UomConversion;

interface UomConversionRepositoryInterface
{
    /**
     * Return all UOM conversions for a given product within a tenant.
     *
     * @return UomConversion[]
     */
    public function findByProduct(int $productId, int $tenantId): array;

    /**
     * Persist a single UOM conversion record.
     */
    public function save(UomConversion $conversion): UomConversion;

    /**
     * Replace all conversions for a product with the provided set.
     *
     * @param  UomConversion[]  $conversions
     */
    public function replaceForProduct(int $productId, int $tenantId, array $conversions): void;

    /**
     * Convert a quantity from one UOM to another for a specific product.
     *
     * The conversion chain is: fromUom → inventory UOM → toUom.
     * Returns null when no conversion path exists.
     */
    public function convert(int $productId, int $tenantId, string $quantity, string $fromUom, string $toUom): ?string;
}
