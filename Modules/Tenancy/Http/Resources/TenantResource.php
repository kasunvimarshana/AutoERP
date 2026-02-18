<?php

declare(strict_types=1);

namespace Modules\Tenancy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tenant Resource
 *
 * Transforms a Tenant model into an API response.
 */
class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'database' => $this->database,
            'schema' => $this->schema,
            'status' => $this->status,
            'settings' => $this->settings,
            'features' => $this->features,
            'limits' => $this->limits,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'subscription_ends_at' => $this->subscription_ends_at?->toIso8601String(),
            'is_active' => $this->isActive(),
            'on_trial' => $this->onTrial(),
            'has_active_subscription' => $this->hasActiveSubscription(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
