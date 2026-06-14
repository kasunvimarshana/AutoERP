<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalUsageEventResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'usage_log_id' => (int) $this->usage_log_id,
            'event_type' => $this->enum($this->event_type),
            'quantity' => (string) $this->quantity,
            'remarks' => $this->remarks,
        ];
    }
}
