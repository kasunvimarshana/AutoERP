<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleJobCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'vehicle_id' => $this->getVehicleId(),
            'job_card_no' => $this->getJobCardNo(),
            'workflow_status' => $this->getWorkflowStatus(),
        ];
    }
}
