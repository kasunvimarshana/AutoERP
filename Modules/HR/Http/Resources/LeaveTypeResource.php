<?php

namespace Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'max_days_per_year' => $this->max_days_per_year,
            'is_paid' => $this->is_paid,
            'requires_approval' => $this->requires_approval,
            'is_carry_forward' => $this->is_carry_forward,
            'max_carry_forward_days' => $this->max_carry_forward_days,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
