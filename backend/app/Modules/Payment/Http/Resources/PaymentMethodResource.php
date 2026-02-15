<?php

declare(strict_types=1);

namespace App\Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
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
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'requires_bank_details' => $this->requires_bank_details,
            'requires_cheque_details' => $this->requires_cheque_details,
            'requires_card_details' => $this->requires_card_details,
            'account_number' => $this->account_number,
            'bank_name' => $this->bank_name,
            'branch_name' => $this->branch_name,
            'swift_code' => $this->swift_code,
            'routing_number' => $this->routing_number,
            'display_order' => $this->display_order,
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
