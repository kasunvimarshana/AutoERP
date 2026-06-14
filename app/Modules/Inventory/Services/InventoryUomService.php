<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\InventoryUomBasis;
use Modules\Inventory\Validators\InventoryValidationService;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemBaseUomConversionService;
use Modules\UOM\Models\UnitOfMeasureModel;

final class InventoryUomService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly ItemBaseUomConversionService $baseUomConversions,
    ) {}

    public function basis(
        int $tenantId,
        ?int $organizationUnitId,
        Item $item,
        ?int $uomId,
        string $quantity,
        string $unitCost = '0.000000',
    ): InventoryUomBasis {
        $enteredQuantity = $this->math->normalize($quantity);
        $enteredUnitCost = $this->math->normalize($unitCost);
        $resolvedUomId = $uomId ?? (int) ($item->base_uom_id ?: 0);
        if ($resolvedUomId <= 0) {
            return new InventoryUomBasis(
                enteredQuantity: $enteredQuantity,
                enteredUnitCost: $enteredUnitCost,
                enteredUomId: null,
                baseQuantity: $enteredQuantity,
                baseUnitCost: $enteredUnitCost,
                conversionFactor: '1.000000',
                baseUomId: null,
            );
        }

        $this->assertUom($tenantId, $organizationUnitId, $resolvedUomId);
        $basis = $this->baseUomConversions->convertOperationalBasis(
            $item,
            $resolvedUomId,
            $enteredQuantity,
            $enteredUnitCost,
        );

        return new InventoryUomBasis(
            enteredQuantity: $enteredQuantity,
            enteredUnitCost: $enteredUnitCost,
            enteredUomId: $resolvedUomId,
            baseQuantity: $this->math->normalize($basis['quantity']),
            baseUnitCost: $this->math->normalize($basis['unit_cost']),
            conversionFactor: $this->math->normalize($basis['factor']),
            baseUomId: (int) ($item->refresh()->base_uom_id ?: $resolvedUomId),
        );
    }

    public function quantity(
        int $tenantId,
        ?int $organizationUnitId,
        Item $item,
        ?int $uomId,
        string $quantity,
    ): string {
        return $this->basis($tenantId, $organizationUnitId, $item, $uomId, $quantity)->baseQuantity;
    }

    private function assertUom(int $tenantId, ?int $organizationUnitId, int $uomId): void
    {
        $uom = UnitOfMeasureModel::query()->findOrFail($uomId);
        $this->validator->assertScope($tenantId, $organizationUnitId, (int) $uom->tenant_id, $uom->organization_unit_id);
        if (isset($uom->is_active) && ! (bool) $uom->is_active) {
            throw new InvalidArgumentException('Inactive UOM cannot be used for inventory.');
        }
    }
}
