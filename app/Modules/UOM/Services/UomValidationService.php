<?php

declare(strict_types=1);

namespace Modules\UOM\Services;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Services\DecimalMath;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Repositories\UomConversionRepositoryInterface;

final class UomValidationService
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $uoms,
        private readonly UomConversionRepositoryInterface $conversions,
        private readonly OrganizationUnitRepositoryInterface $organizationUnits,
        private readonly DecimalMath $math,
    ) {}

    public function organizationBelongsToTenant(?int $organizationUnitId, int $tenantId): bool
    {
        if ($organizationUnitId === null) {
            return true;
        }

        $organizationUnit = $this->organizationUnits->findById($organizationUnitId);

        return $organizationUnit !== null && (int) $organizationUnit->get('tenant_id') === $tenantId;
    }

    public function duplicateCodeExists(string $code, int $tenantId, ?int $excludeId = null): bool
    {
        $existing = $this->uoms->findByCode(strtoupper(trim($code)), $tenantId);

        return $existing !== null && ($excludeId === null || (int) $existing->get('id') !== $excludeId);
    }

    public function decimalPrecisionIsValid(int $precision): bool
    {
        return $precision >= 0 && $precision <= 8;
    }

    /**
     * @return array{valid:bool,code:string|null,message:string|null,from:DataRecord|null,to:DataRecord|null}
     */
    public function validateConversion(int $fromUomId, int $toUomId, int|string $factor, int $tenantId): array
    {
        if ($fromUomId === $toUomId) {
            return $this->invalid(UomErrorCode::SELF_REFERENCE_CONVERSION, 'From and to UOM cannot be the same.');
        }

        if ($this->math->compare($factor, '0') <= 0) {
            return $this->invalid(UomErrorCode::INVALID_FACTOR, 'Conversion factor must be greater than zero.');
        }

        $from = $this->uoms->findByIdInTenant($fromUomId, $tenantId);
        $to = $this->uoms->findByIdInTenant($toUomId, $tenantId);
        if ($from === null || $to === null) {
            return $this->invalid(UomErrorCode::NOT_FOUND, 'One or both units of measure were not found.');
        }

        if (! $from->get('is_active') || ! $to->get('is_active')) {
            return $this->invalid(UomErrorCode::INVALID_VALUE, 'Only active units of measure can be used in conversions.');
        }

        if ((string) $from->get('type') !== (string) $to->get('type')) {
            return $this->invalid(UomErrorCode::INCOMPATIBLE_UOM_TYPE, 'Conversion types are incompatible.');
        }

        return ['valid' => true, 'code' => null, 'message' => null, 'from' => $from, 'to' => $to];
    }

    private function invalid(string $code, string $message): array
    {
        return ['valid' => false, 'code' => $code, 'message' => $message, 'from' => null, 'to' => null];
    }
}
