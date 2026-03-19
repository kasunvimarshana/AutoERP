<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'device_id'      => $this->device_id,
            'device_name'    => $this->device_name,
            'device_type'    => $this->device_type,
            'platform'       => $this->platform,
            'is_trusted'     => $this->is_trusted,
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
