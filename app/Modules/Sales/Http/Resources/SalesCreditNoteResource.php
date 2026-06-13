<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesCreditNoteResource extends ModuleResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'credit_note_number' => $this->credit_note_number,
            'credit_note_date' => $this->credit_note_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => $this->statusLabel($this->status),
            'customer' => $this->whenLoaded('customer', fn () => $this->summary($this->customer, ['customer_number', 'code', 'name', 'display_name'])),
            'sales_return' => $this->whenLoaded('salesReturn', fn () => $this->summary($this->salesReturn, ['return_number', 'return_date', 'status'])),
            'amount' => (string) $this->amount,
            'allocated_amount' => (string) $this->allocated_amount,
            'remaining_amount' => (string) $this->remaining_amount,
            'reason' => $this->reason,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
