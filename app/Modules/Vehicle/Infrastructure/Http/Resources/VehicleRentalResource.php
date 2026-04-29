<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleRentalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'vehicle_id' => $this->getVehicleId(),
            'rental_no' => $this->getRentalNo(),
            'rental_status' => $this->getRentalStatus(),
            'pricing_model' => $this->getPricingModel(),
            'grand_total' => $this->getGrandTotal(),
        ];
    }
}
