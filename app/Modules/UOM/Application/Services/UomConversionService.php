<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Services;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;

final class UomConversionService implements UomConversionServiceInterface
{
    private const PRECISION = 10;

    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
        private readonly UomConversionRepositoryInterface $conversionRepository,
    ) {
    }

    public function convert(
        float $quantity,
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
        ?int $itemId = null,
    ): Result {
        $factorResult = $this->getConversionFactor($fromUomId, $toUomId, $tenantId, $itemId);

        if ($factorResult->isFailure()) {
            return $factorResult;
        }

        $factor = (float) $factorResult->valueOrFail();

        return Result::success(round($quantity * $factor, self::PRECISION));
    }

    public function canConvert(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
        ?int $itemId = null,
    ): bool {
        return $this->getConversionFactor($fromUomId, $toUomId, $tenantId, $itemId)->isSuccess();
    }

    public function getConversionFactor(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
        ?int $itemId = null,
    ): Result {
        // Trivial: same unit
        if ($fromUomId == $toUomId) {
            return Result::success(1.0);
        }

        // 1. Item-specific conversion (priority)
        if ($itemId !== null) {
            $result = $this->resolveDirectFactor($fromUomId, $toUomId, $tenantId, $itemId);
            if ($result !== null) {
                return Result::success($result);
            }
        }

        // 2. Global (tenant-wide) conversion
        $result = $this->resolveDirectFactor($fromUomId, $toUomId, $tenantId, null);
        if ($result !== null) {
            return Result::success($result);
        }

        // 3. Mediate through the base unit of this type
        return $this->resolveViaBaseUnit($fromUomId, $toUomId, $tenantId);
    }

    public function getBaseUnit(string $type, int $tenantId): Result
    {
        $record = $this->uomRepository->findBaseUomForType($type, $tenantId);

        if ($record === null) {
            return Result::failure(new Error(UomErrorCode::NOT_FOUND, "No base unit found for type '{$type}'."));
        }

        return Result::success($record);
    }

    public function normalizeToBase(
        float $quantity,
        int|string $uomId,
        int $tenantId,
    ): Result {
        $uom = $this->uomRepository->findByIdInTenant($uomId, $tenantId);

        if ($uom === null) {
            return Result::failure(new Error(UomErrorCode::NOT_FOUND, 'Unit of measure not found.'));
        }

        if ($uom->get('is_base')) {
            return Result::success($quantity);
        }

        $base = $this->uomRepository->findBaseUomForType((string) $uom->get('type'), $tenantId);

        if ($base === null) {
            return Result::failure(new Error(UomErrorCode::NOT_FOUND, "No base unit for type '{$uom->get('type')}'."));
        }

        return $this->convert($quantity, $uomId, $base->get('id'), $tenantId);
    }

    public function convertFromBase(
        float $quantity,
        int|string $baseUomId,
        int|string $targetUomId,
        int $tenantId,
    ): Result {
        return $this->convert($quantity, $baseUomId, $targetUomId, $tenantId);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Attempt to resolve a direct conversion factor (forward or bidirectional reverse).
     * Returns null when no active conversion record is found.
     */
    private function resolveDirectFactor(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
        ?int $itemId,
    ): ?float {
        // Forward
        $record = $this->conversionRepository->findConversionBetween($fromUomId, $toUomId, $tenantId, $itemId);

        if ($record !== null && $record->get('is_active')) {
            return (float) $record->get('factor');
        }

        // Bidirectional reverse
        $reverse = $this->conversionRepository->findConversionBetween($toUomId, $fromUomId, $tenantId, $itemId);

        if ($reverse !== null && $reverse->get('is_active') && $reverse->get('is_bidirectional')) {
            $factor = (float) $reverse->get('factor');

            return $factor != 0.0 ? round(1.0 / $factor, self::PRECISION) : null;
        }

        return null;
    }

    /**
     * Resolve factor by mediating through the base unit of the shared type.
     */
    private function resolveViaBaseUnit(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
    ): Result {
        $fromUom = $this->uomRepository->findByIdInTenant($fromUomId, $tenantId);
        $toUom   = $this->uomRepository->findByIdInTenant($toUomId, $tenantId);

        if ($fromUom === null || $toUom === null) {
            return Result::failure(new Error(UomErrorCode::NOT_FOUND, 'One or both units of measure not found.'));
        }

        if ($fromUom->get('type') !== $toUom->get('type')) {
            return Result::failure(new Error(
                UomErrorCode::INCOMPATIBLE_UOM_TYPE,
                "Cannot convert between types '{$fromUom->get('type')}' and '{$toUom->get('type')}'.",
            ));
        }

        $base = $this->uomRepository->findBaseUomForType((string) $fromUom->get('type'), $tenantId);

        if ($base === null) {
            return Result::failure(new Error(
                UomErrorCode::CONVERSION_NOT_FOUND,
                'No base unit defined for type; cannot mediate conversion.',
            ));
        }

        $baseId = $base->get('id');

        // from -> base
        $fromToBase = ($fromUomId == $baseId)
            ? 1.0
            : $this->resolveDirectFactor($fromUomId, $baseId, $tenantId, null);

        // base -> target
        $baseToTarget = ($toUomId == $baseId)
            ? 1.0
            : $this->resolveDirectFactor($baseId, $toUomId, $tenantId, null);

        if ($fromToBase === null || $baseToTarget === null) {
            return Result::failure(new Error(
                UomErrorCode::CONVERSION_NOT_FOUND,
                'No conversion path found between the specified units.',
            ));
        }

        return Result::success(round($fromToBase * $baseToTarget, self::PRECISION));
    }
}
