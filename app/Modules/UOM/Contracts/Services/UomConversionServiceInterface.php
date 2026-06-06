<?php

declare(strict_types=1);

namespace Modules\UOM\Contracts\Services;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Result;

interface UomConversionServiceInterface
{
    /**
     * Convert a quantity from one unit to another within a tenant scope.
     * Resolves item-specific conversions first, then falls back to global
     * tenant conversions, then attempts base-unit mediation.
     *
     * @return Result<float>
     */
    public function convert(
        float $quantity,
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
        ?int $itemId = null,
    ): Result;

    /**
     * Check whether a conversion path exists between two units.
     */
    public function canConvert(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
        ?int $itemId = null,
    ): bool;

    /**
     * Return the net conversion factor from fromUom to toUom.
     *
     * @return Result<float>
     */
    public function getConversionFactor(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
        ?int $itemId = null,
    ): Result;

    /**
     * Return the base unit for the given type within the tenant.
     *
     * @return Result<DataRecord>
     */
    public function getBaseUnit(string $type, int $tenantId): Result;

    /**
     * Convert a quantity to its base unit within the tenant.
     *
     * @return Result<float>
     */
    public function normalizeToBase(
        float $quantity,
        int|string $uomId,
        int $tenantId,
    ): Result;

    /**
     * Convert a quantity from a base unit to a target unit within the tenant.
     *
     * @return Result<float>
     */
    public function convertFromBase(
        float $quantity,
        int|string $baseUomId,
        int|string $targetUomId,
        int $tenantId,
    ): Result;
}
