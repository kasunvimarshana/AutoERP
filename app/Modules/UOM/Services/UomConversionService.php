<?php

declare(strict_types=1);

namespace Modules\UOM\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Contracts\Services\UomConversionServiceInterface;
use Modules\UOM\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Repositories\UomConversionRepositoryInterface;

final class UomConversionService implements UomConversionServiceInterface
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
        private readonly UomConversionRepositoryInterface $conversionRepository,
        private readonly DecimalMath $math,
    ) {}

    public function convert(
        int|string $quantity,
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
    ): Result {
        $factorResult = $this->getConversionFactor($fromUomId, $toUomId, $tenantId);

        if ($factorResult->isFailure()) {
            return $factorResult;
        }

        return Result::success($this->math->mul($quantity, (string) $factorResult->valueOrFail()));
    }

    public function canConvert(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
    ): bool {
        return $this->getConversionFactor($fromUomId, $toUomId, $tenantId)->isSuccess();
    }

    public function getConversionFactor(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
    ): Result {
        // Trivial: same unit
        if ($fromUomId == $toUomId) {
            return Result::success('1.000000');
        }

        // 1. Tenant-wide direct conversion
        $result = $this->resolveDirectFactor($fromUomId, $toUomId, $tenantId);
        if ($result !== null) {
            return Result::success($result);
        }

        // 2. Mediate through the base unit of this type
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
        int|string $quantity,
        int|string $uomId,
        int $tenantId,
    ): Result {
        $uom = $this->uomRepository->findByIdInTenant($uomId, $tenantId);

        if ($uom === null) {
            return Result::failure(new Error(UomErrorCode::NOT_FOUND, 'Unit of measure not found.'));
        }

        if ($uom->get('is_base')) {
            return Result::success($this->math->normalize($quantity));
        }

        $base = $this->uomRepository->findBaseUomForType((string) $uom->get('type'), $tenantId);

        if ($base === null) {
            return Result::failure(new Error(UomErrorCode::NOT_FOUND, "No base unit for type '{$uom->get('type')}'."));
        }

        return $this->convert($quantity, $uomId, $base->get('id'), $tenantId);
    }

    public function convertFromBase(
        int|string $quantity,
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
     * Attempt to resolve a direct conversion factor.
     * Returns null when no active conversion record is found.
     */
    private function resolveDirectFactor(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
    ): ?string {
        // Forward
        $record = $this->conversionRepository->findConversionBetween($fromUomId, $toUomId, $tenantId);

        if ($record !== null && $record->get('is_active')) {
            return $this->math->normalize((string) $record->get('conversion_factor'));
        }

        // Reverse conversion is available when the opposite direct conversion exists.
        $reverse = $this->conversionRepository->findConversionBetween($toUomId, $fromUomId, $tenantId);

        if ($reverse !== null && $reverse->get('is_active')) {
            $factor = $this->math->normalize((string) $reverse->get('conversion_factor'));

            return $this->math->isZero($factor) ? null : $this->math->div('1', $factor);
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
        $toUom = $this->uomRepository->findByIdInTenant($toUomId, $tenantId);

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
            ? '1.000000'
            : $this->resolveDirectFactor($fromUomId, $baseId, $tenantId);

        // base -> target
        $baseToTarget = ($toUomId == $baseId)
            ? '1.000000'
            : $this->resolveDirectFactor($baseId, $toUomId, $tenantId);

        if ($fromToBase === null || $baseToTarget === null) {
            return Result::failure(new Error(
                UomErrorCode::CONVERSION_NOT_FOUND,
                'No conversion path found between the specified units.',
            ));
        }

        return Result::success($this->math->mul($fromToBase, $baseToTarget));
    }
}
