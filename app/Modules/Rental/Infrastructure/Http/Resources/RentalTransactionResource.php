<?php declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'agreement_id' => $this->resource->getAgreementId(),
            'vehicle_id' => $this->resource->getVehicleId(),
            'odometer_start' => $this->resource->getOdometerStart(),
            'odometer_end' => $this->resource->getOdometerEnd(),
            'fuel_level_start' => $this->resource->getFuelLevelStart(),
            'fuel_level_end' => $this->resource->getFuelLevelEnd(),
            'check_out_at' => $this->resource->getCheckOutAt()->format('Y-m-d H:i:s'),
            'check_in_at' => $this->resource->getCheckInAt()?->format('Y-m-d H:i:s'),
            'status' => $this->resource->getStatus(),
        ];
    }
}
