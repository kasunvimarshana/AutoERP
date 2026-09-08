<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OwnerSourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->reference.' · '.$this->party_name_snapshot,
            'starts_on' => $this->starts_on->toDateString(), 'ends_on' => $this->ends_on?->toDateString()];
    }
}
