<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PlatformSessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $user = $this->resource->relationLoaded('operator') ? $this->resource->operator : null;

        return [
            'id' => (string) $this->resource->getAttribute('public_id'),
            'operator' => $user === null ? null : [
                'id' => (int) $user->getKey(),
                'name' => trim((string) $user->getAttribute('first_name').' '.(string) $user->getAttribute('last_name')),
                'email' => (string) $user->getAttribute('email'),
                'status' => (string) $user->getAttribute('status'),
            ],
            'status' => (string) $this->resource->getAttribute('status'),
            'ip_address' => $this->resource->getAttribute('ip_address'),
            'device_name' => $this->resource->getAttribute('device_name'),
            'user_agent' => $this->resource->getAttribute('user_agent'),
            'authenticated_at' => $this->resource->getAttribute('authenticated_at')?->toISOString(),
            'last_activity_at' => $this->resource->getAttribute('last_activity_at')?->toISOString(),
            'expires_at' => $this->resource->getAttribute('expires_at')?->toISOString(),
            'revoked_at' => $this->resource->getAttribute('revoked_at')?->toISOString(),
        ];
    }
}
