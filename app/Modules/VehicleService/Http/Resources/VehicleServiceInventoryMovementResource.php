<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class VehicleServiceInventoryMovementResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'movement_number' => $this->movement_number,
            'source_line_id' => $this->source_line_id,
            'quantity' => (string) $this->quantity,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
        ];
    }
}
