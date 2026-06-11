<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Sales\DTOs\SalesHeaderAdjustmentData;
use Modules\Sales\Models\SalesHeaderAdjustment;

final class SalesHeaderAdjustmentService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function create(
        int $tenantId,
        ?int $organizationUnitId,
        string $sourceType,
        int $sourceId,
        SalesHeaderAdjustmentData $data,
        ?string $amount = null,
    ): SalesHeaderAdjustment {
        $value = $this->math->normalize($amount ?? $data->amount);

        return SalesHeaderAdjustment::query()->create([
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

    public function cloneProportionally(SalesHeaderAdjustment $source, string $sourceType, int $sourceId, string $ratio): SalesHeaderAdjustment
    {
        $amount = $this->math->mul((string) $source->amount, $ratio);

        return SalesHeaderAdjustment::query()->create([
            'tenant_id' => $source->tenant_id,
            'organization_unit_id' => $source->organization_unit_id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'name' => $source->name,
            'adjustment_type' => $source->adjustment_type,
            'effect' => $source->effect,
            'calculation_type' => $source->calculation_type,
            'calculation_base' => $source->calculation_base,
            'rate' => $source->rate,
            'amount' => $amount,
            'allocated_amount' => '0.000000',
            'returned_amount' => '0.000000',
            'remaining_amount' => $amount,
            'allocation_method' => $source->allocation_method,
            'is_allocatable' => $source->is_allocatable,
            'sort_order' => $source->sort_order,
            'description' => $source->description,
        ]);
    }
}
