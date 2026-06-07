<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EmployeeAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->getKey(), 'availability_date' => $this->availability_date?->toDateString(), 'availability_status' => $this->availability_status instanceof BackedEnum ? $this->availability_status->value : $this->availability_status, 'source_type' => $this->source_type, 'source_id' => $this->source_id, 'reason' => $this->reason, 'starts_at' => $this->starts_at?->toISOString(), 'ends_at' => $this->ends_at?->toISOString()];
    }
}
