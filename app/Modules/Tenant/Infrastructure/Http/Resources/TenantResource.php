<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Resources\BaseResource;

/**
 * @mixin \Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel
 */
final class TenantResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'uuid'                 => $this->uuid,
            'name'                 => $this->name,
            'slug'                 => $this->slug,
            'status'               => $this->status,
            'plan'                 => $this->plan,
            'domain'               => $this->domain,
            'logo_path'            => $this->logo_path,
            'settings'             => $this->settings,
            'trial_ends_at'        => $this->trial_ends_at?->toIso8601String(),
            'subscription_ends_at' => $this->subscription_ends_at?->toIso8601String(),
            'metadata'             => $this->metadata,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
