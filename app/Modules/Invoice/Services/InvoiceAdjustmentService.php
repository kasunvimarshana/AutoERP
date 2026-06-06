<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceAdjustment;
use Modules\Invoice\Models\InvoiceAdjustmentAllocation;

final class InvoiceAdjustmentService
{
    /**
     * @param  list<array{adjustment: InvoiceAdjustmentData, allocation: array<string, mixed>|null}>  $preparedAdjustments
     */
    public function createAdjustments(Invoice $invoice, array $preparedAdjustments): void
    {
        foreach ($preparedAdjustments as $prepared) {
            $adjustment = $prepared['adjustment'];

            $model = InvoiceAdjustment::query()->create([
                'tenant_id' => $invoice->tenant_id,
                'organization_unit_id' => $invoice->organization_unit_id,
                'invoice_id' => $invoice->getKey(),
                'source_adjustment_type' => $adjustment->sourceAdjustmentType,
                'source_adjustment_id' => $adjustment->sourceAdjustmentId,
                'source_type' => $adjustment->sourceType,
                'source_id' => $adjustment->sourceId,
                'name' => $adjustment->name,
                'adjustment_type' => $adjustment->adjustmentType->value,
                'effect' => $adjustment->effect->value,
                'calculation_type' => $adjustment->calculationType,
                'rate' => $adjustment->rate,
                'amount' => $adjustment->amount,
                'allocation_method' => $adjustment->allocationMethod->value,
                'is_system_generated' => $adjustment->isSystemGenerated,
                'description' => $adjustment->description,
            ]);

            if ($prepared['allocation'] !== null) {
                InvoiceAdjustmentAllocation::query()->create(array_merge($prepared['allocation'], [
                    'tenant_id' => $invoice->tenant_id,
                    'organization_unit_id' => $invoice->organization_unit_id,
                    'invoice_id' => $invoice->getKey(),
                    'invoice_adjustment_id' => $model->getKey(),
                ]));
            }
        }
    }

    /**
     * @param  list<array{adjustment: InvoiceAdjustmentData, allocation: array<string, mixed>|null}>  $preparedAdjustments
     * @return array<string, string>
     */
    public function allocatedAdjustmentAmountBySource(array $preparedAdjustments, DecimalMath $math): array
    {
        $totals = [];
        foreach ($preparedAdjustments as $prepared) {
            if ($prepared['allocation'] === null) {
                continue;
            }

            $sourceKey = $prepared['allocation']['source_type'].':'.$prepared['allocation']['source_id'];
            $totals[$sourceKey] = $math->add(
                $totals[$sourceKey] ?? '0.000000',
                (string) $prepared['allocation']['allocated_amount'],
            );
        }

        return $totals;
    }
}
