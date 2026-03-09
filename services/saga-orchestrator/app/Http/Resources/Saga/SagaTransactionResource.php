<?php

declare(strict_types=1);

namespace App\Http\Resources\Saga;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Saga Transaction Resource - formats saga state for API responses.
 */
class SagaTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'saga_type' => $this->saga_type,
            'status' => $this->status,
            'failure_reason' => $this->failure_reason,
            'retry_count' => $this->retry_count,
            'payload' => $this->payload,
            'result' => $this->result,
            'steps' => $this->steps->map(fn ($step) => [
                'id' => $step->id,
                'step_order' => $step->step_order,
                'step_name' => $step->step_name,
                'service' => $step->service,
                'status' => $step->status,
                'failure_reason' => $step->failure_reason,
                'retry_count' => $step->retry_count,
                'started_at' => $step->started_at?->toISOString(),
                'completed_at' => $step->completed_at?->toISOString(),
                'compensated_at' => $step->compensated_at?->toISOString(),
            ]),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
