<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalUsageFactResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'financial_side' => $this->enumValue($this->financial_side),
            'context_id' => (int) $this->usage_context_id,
            'usage_log_id' => (int) $this->usage_log_id,
            'agreement' => $this->whenLoaded('context', fn () => $this->context->relationLoaded('agreement')
                ? $this->summary($this->context->agreement, ['agreement_number', 'agreement_kind'])
                : null),
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'start_odometer' => $this->decimal($this->start_odometer),
            'end_odometer' => $this->decimal($this->end_odometer),
            'commercial_distance_km' => $this->decimal($this->commercial_distance_km),
            'working_minutes' => (int) $this->working_minutes,
            'normal_overtime_minutes' => (int) $this->normal_overtime_minutes,
            'double_overtime_minutes' => (int) $this->double_overtime_minutes,
            'triple_overtime_minutes' => (int) $this->triple_overtime_minutes,
            'night_out_count' => $this->decimal($this->night_out_count),
            'reference_number' => $this->reference_number,
            'variance_reason' => $this->variance_reason,
            'status' => $this->enumValue($this->status),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'reversed_at' => $this->reversed_at?->toISOString(),
            'reversal_reason' => $this->reversal_reason,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
