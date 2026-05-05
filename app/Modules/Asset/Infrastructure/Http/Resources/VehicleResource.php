<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'asset_id' => $this->resource->getAssetId(),
            'registration_number' => $this->resource->getRegistrationNumber(),
            'make' => $this->resource->getMake(),
            'model' => $this->resource->getModel(),
            'year' => $this->resource->getYear(),
            'vin' => $this->resource->getVin(),
            'fuel_type' => $this->resource->getFuelType(),
            'current_mileage' => $this->resource->getCurrentMileage(),
            'status' => $this->resource->getStatus(),
        ];
    }
}
