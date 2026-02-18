<?php

namespace Modules\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerformanceReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'reviewer' => new EmployeeResource($this->whenLoaded('reviewer')),
            'review_period_start' => $this->review_period_start?->toDateString(),
            'review_period_end' => $this->review_period_end?->toDateString(),
            'overall_rating' => $this->overall_rating,
            'strengths' => $this->strengths,
            'areas_for_improvement' => $this->areas_for_improvement,
            'goals' => $this->goals,
            'achievements' => $this->achievements,
            'comments' => $this->comments,
            'status' => $this->status,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
