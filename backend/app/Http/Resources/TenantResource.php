<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Tenant.
 */
final class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'domain'     => $this->domain,
            'plan'       => $this->plan,
            'status'     => $this->status,
            'max_users'  => $this->max_users,
            'timezone'   => $this->timezone,
            'locale'     => $this->locale,
            'currency'   => $this->currency,
            'logo_url'   => $this->logo_url,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
