<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EmployeeCertificationAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->getKey(), 'certification_id' => $this->certification_id, 'certification' => $this->whenLoaded('certification', fn () => new HrCertificationResource($this->certification)), 'certificate_number' => $this->certificate_number, 'issued_date' => $this->issued_date?->toDateString(), 'expiry_date' => $this->expiry_date?->toDateString(), 'status' => $this->status instanceof BackedEnum ? $this->status->value : $this->status];
    }
}
