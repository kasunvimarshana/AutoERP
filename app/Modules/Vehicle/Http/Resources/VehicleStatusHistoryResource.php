<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vehicle\Http\Resources\Concerns\FormatsVehicleResources;

final class VehicleStatusHistoryResource extends JsonResource
{
    use FormatsVehicleResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'old_status' => $this->enumValue($this->old_status),
            'new_status' => $this->enumValue($this->new_status),
            'reason' => $this->reason,
            'changed_by' => $this->changed_by,
            'changed_at' => $this->changed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
