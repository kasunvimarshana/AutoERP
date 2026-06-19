<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;

final class PurchaseHeaderAdjustmentService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseAdjustmentPolicyResolver $policies,
    ) {}

    public function create(
        int $tenantId,
        ?int $organizationUnitId,
        string $sourceType,
        int $sourceId,
        PurchaseHeaderAdjustmentData $data,
        ?string $amount = null,
        ?int $userId = null,
        string $fieldPrefix = 'adjustments',
    ): PurchaseHeaderAdjustment {
        $value = $this->math->normalize($amount ?? $data->amount);
        $policy = $this->policies->resolveForData($data, $tenantId, $organizationUnitId, $fieldPrefix, $userId);

        $model = PurchaseHeaderAdjustment::query()->create([
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
            'finance_posting_profile_id' => $policy['finance_posting_profile_id'],
            'finance_account_id' => $policy['finance_account_id'],
            'cost_treatment' => $policy['cost_treatment'],
            'tax_treatment' => $policy['tax_treatment'],
            'mapping_source' => $policy['mapping_source'],
            'override_reason' => $data->overrideReason,
            'sort_order' => $data->sortOrder,
            'description' => $data->description,
        ]);

        if ($data->manualAllocations !== []) {
            $model->setAttribute('manual_allocation_payload', $data->manualAllocations);
        }

        return $model;
    }

    public function cloneProportionally(PurchaseHeaderAdjustment $adjustment, string $sourceType, int $sourceId, string $ratio): PurchaseHeaderAdjustment
    {
        $amount = $this->math->mul((string) $adjustment->amount, $ratio);

        return $this->cloneForAmount($adjustment, $sourceType, $sourceId, $amount);
    }

    public function cloneForAmount(
        PurchaseHeaderAdjustment $adjustment,
        string $sourceType,
        int $sourceId,
        string $amount,
        ?int $originPurchaseHeaderAdjustmentId = null,
    ): PurchaseHeaderAdjustment {
        $amount = $this->math->normalize($amount);
        $originId = $originPurchaseHeaderAdjustmentId
            ?? $adjustment->origin_purchase_header_adjustment_id
            ?? (int) $adjustment->getKey();

        return PurchaseHeaderAdjustment::query()->create([
            'tenant_id' => $adjustment->tenant_id,
            'organization_unit_id' => $adjustment->organization_unit_id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'origin_purchase_header_adjustment_id' => $originId,
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
            'finance_posting_profile_id' => $adjustment->finance_posting_profile_id,
            'finance_account_id' => $adjustment->finance_account_id,
            'cost_treatment' => $adjustment->cost_treatment,
            'tax_treatment' => $adjustment->tax_treatment,
            'mapping_source' => $adjustment->mapping_source,
            'override_reason' => $adjustment->override_reason,
            'sort_order' => $adjustment->sort_order,
            'description' => $adjustment->description,
        ]);
    }
}
