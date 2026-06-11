<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Inventory\DTOs\CostAdjustmentData;
use Modules\Inventory\DTOs\CostAdjustmentLineData;

final class StoreCostAdjustmentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'adjustment_date' => ['required', 'date'],
            'adjustment_number' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.valuation_layer_id' => ['required', 'integer', 'min:1'],
            'lines.*.adjustment_amount' => ['required', 'decimal:0,6', 'not_in:0,0.0,0.00,0.000000'],
            'lines.*.reason' => ['nullable', 'string'],
        ];
    }

    public function toData(): CostAdjustmentData
    {
        return new CostAdjustmentData(
            tenantId: $this->tenantId(),
            adjustmentDate: (string) $this->input('adjustment_date'),
            organizationUnitId: $this->organizationUnitId(),
            adjustmentNumber: $this->filled('adjustment_number') ? (string) $this->input('adjustment_number') : null,
            reason: $this->filled('reason') ? (string) $this->input('reason') : null,
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
            createdBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): CostAdjustmentLineData => new CostAdjustmentLineData(
                valuationLayerId: (int) $row['valuation_layer_id'],
                adjustmentAmount: (string) $row['adjustment_amount'],
                reason: isset($row['reason']) ? (string) $row['reason'] : null,
            ), $this->input('lines')),
        );
    }
}
