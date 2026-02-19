<?php

declare(strict_types=1);

namespace Modules\Invoice\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payment Resource
 *
 * Transforms Payment model for API responses
 */
class PaymentResource extends JsonResource
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
            'payment_number' => $this->payment_number,
            'payment_date' => $this->payment_date?->toDateString(),
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'processed_by' => $this->processed_by,
            'invoice' => $this->whenLoaded('invoice'),
            'processed_by_user' => $this->whenLoaded('processedBy'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
