<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'driver_id' => $this->resource->driverId,
            'available_from' => $this->resource->availableFrom->format('Y-m-d H:i:s'),
            'available_to' => $this->resource->availableTo->format('Y-m-d H:i:s'),
            'days_of_week' => $this->resource->daysOfWeek,
        ];
    }
}
