<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Invoice\Http\Resources\Concerns\FormatsInvoiceResources;

final class InvoiceAdjustmentResource extends JsonResource
{
    use FormatsInvoiceResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'source_adjustment_type' => $this->source_adjustment_type,
            'source_adjustment_id' => $this->source_adjustment_id,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'name' => $this->name,
            'adjustment_type' => $this->enumValue($this->adjustment_type),
            'effect' => $this->enumValue($this->effect),
            'calculation_type' => $this->calculation_type,
            'rate' => (string) $this->rate,
            'amount' => (string) $this->amount,
            'allocation_method' => $this->enumValue($this->allocation_method),
            'is_system_generated' => (bool) $this->is_system_generated,
            'description' => $this->description,
            'allocations' => $this->whenLoaded(
                'allocations',
                fn () => InvoiceAdjustmentAllocationResource::collection($this->allocations)
                    ->resolve($request),
                [],
            ),
        ];
    }
}
