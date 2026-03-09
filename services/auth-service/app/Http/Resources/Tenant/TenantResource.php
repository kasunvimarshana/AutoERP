<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'subdomain' => $this->subdomain,
            'plan'      => $this->plan,
            'status'    => $this->status,
            'features'  => $this->features,
            'settings'  => $this->when(
                $request->user()?->hasRole('super-admin'),
                fn() => $this->settings
            ),
            'config'    => $this->when(
                $request->user()?->hasRole('super-admin'),
                fn() => $this->config
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
