<?php declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'vehicle_id' => $this->resource->getVehicleId(),
            'customer_id' => $this->resource->getCustomerId(),
            'driver_id' => $this->resource->getDriverId(),
            'reservation_number' => $this->resource->getReservationNumber(),
            'start_at' => $this->resource->getStartAt()->format('Y-m-d H:i:s'),
            'expected_return_at' => $this->resource->getExpectedReturnAt()->format('Y-m-d H:i:s'),
            'billing_unit' => $this->resource->getBillingUnit(),
            'base_rate' => $this->resource->getBaseRate(),
            'estimated_distance' => $this->resource->getEstimatedDistance(),
            'estimated_amount' => $this->resource->getEstimatedAmount(),
            'status' => $this->resource->getStatus(),
            'notes' => $this->resource->getNotes(),
        ];
    }
}
