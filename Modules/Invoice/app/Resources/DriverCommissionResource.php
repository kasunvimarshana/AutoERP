<?php

declare(strict_types=1);

namespace Modules\Invoice\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * DriverCommission Resource
 *
 * Transforms DriverCommission model for API responses
 */
class DriverCommissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'driver_id' => $this->driver_id,
            'commission_rate' => (float) $this->commission_rate,
            'commission_amount' => (float) $this->commission_amount,
            'status' => $this->status,
            'paid_date' => $this->paid_date?->toDateString(),
            'notes' => $this->notes,
            'approved_by' => $this->approved_by,
            'invoice' => $this->whenLoaded('invoice'),
            'driver' => $this->whenLoaded('driver'),
            'approved_by_user' => $this->whenLoaded('approvedBy'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
