<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Supplier Resource
 *
 * Transforms supplier data for API responses.
 */
class SupplierResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'website' => $this->website,
            'tax_id' => $this->tax_id,
            'payment_terms' => $this->payment_terms,
            'credit_limit' => $this->credit_limit,
            'currency' => $this->currency,
            'rating' => $this->rating,
            'status' => $this->status,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
