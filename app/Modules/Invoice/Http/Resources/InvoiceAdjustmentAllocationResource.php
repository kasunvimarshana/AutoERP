<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;
use Modules\Invoice\Http\Resources\Concerns\FormatsInvoiceResources;

final class InvoiceAdjustmentAllocationResource extends ModuleResource
{
    use FormatsInvoiceResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'invoice_adjustment_id' => $this->invoice_adjustment_id,
            'source_adjustment_type' => $this->source_adjustment_type,
            'source_adjustment_id' => (int) $this->source_adjustment_id,
            'source_type' => $this->source_type,
            'source_id' => (int) $this->source_id,
            'adjustment_type' => $this->enumValue($this->adjustment_type),
            'effect' => $this->enumValue($this->effect),
            'allocation_method' => $this->enumValue($this->allocation_method),
            'source_amount' => (string) $this->source_amount,
            'previously_allocated_amount' => (string) $this->previously_allocated_amount,
            'allocated_amount' => (string) $this->allocated_amount,
            'remaining_amount' => (string) $this->remaining_amount,
        ];
    }
}
