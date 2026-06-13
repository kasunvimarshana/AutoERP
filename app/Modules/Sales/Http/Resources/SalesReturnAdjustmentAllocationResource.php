<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesReturnAdjustmentAllocationResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'adjustment_type' => $this->enumValue($this->adjustment_type),
            'effect' => $this->enumValue($this->effect),
            'source_amount' => (string) $this->source_amount,
            'previously_returned_amount' => (string) $this->previously_returned_amount,
            'returned_amount' => (string) $this->returned_amount,
            'remaining_amount' => (string) $this->remaining_amount,
        ];
    }
}
