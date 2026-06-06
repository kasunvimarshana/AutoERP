<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;

final class PurchaseHeaderAdjustmentService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function create(
        int $tenantId,
        ?int $organizationUnitId,
        string $sourceType,
        int $sourceId,
        PurchaseHeaderAdjustmentData $data,
        ?string $amount = null,
    ): PurchaseHeaderAdjustment {
        $value = $this->math->normalize($amount ?? $data->amount);

        return PurchaseHeaderAdjustment::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'name' => $data->name,
            'adjustment_type' => $data->adjustmentType,
            'effect' => $data->effect,
            'calculation_type' => $data->calculationType,
            'calculation_base' => $data->calculationBase,
            'rate' => $this->math->normalize($data->rate),
            'amount' => $value,
            'allocated_amount' => '0.000000',
            'returned_amount' => '0.000000',
            'remaining_amount' => $value,
            'allocation_method' => $data->allocationMethod,
            'is_allocatable' => $data->isAllocatable,
            'sort_order' => $data->sortOrder,
            'description' => $data->description,
        ]);
    }

    public function cloneProportionally(PurchaseHeaderAdjustment $adjustment, string $sourceType, int $sourceId, string $ratio): PurchaseHeaderAdjustment
    {
        $amount = $this->math->mul((string) $adjustment->amount, $ratio);

        return PurchaseHeaderAdjustment::query()->create([
            'tenant_id' => $adjustment->tenant_id,
            'organization_unit_id' => $adjustment->organization_unit_id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'name' => $adjustment->name,
            'adjustment_type' => $adjustment->adjustment_type,
            'effect' => $adjustment->effect,
            'calculation_type' => $adjustment->calculation_type,
            'calculation_base' => $adjustment->calculation_base,
            'rate' => $adjustment->rate,
            'amount' => $amount,
            'allocated_amount' => '0.000000',
            'returned_amount' => '0.000000',
            'remaining_amount' => $amount,
            'allocation_method' => $adjustment->allocation_method,
            'is_allocatable' => $adjustment->is_allocatable,
            'sort_order' => $adjustment->sort_order,
            'description' => $adjustment->description,
        ]);
    }
}
