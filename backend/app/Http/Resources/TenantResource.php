<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'domain'        => $this->domain,
            'plan'          => $this->plan,
            'status'        => $this->status,
            'max_users'     => $this->max_users,
            'max_products'  => $this->max_products,
            'is_on_trial'   => $this->isOnTrial(),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'subscribed_at' => $this->subscribed_at?->toIso8601String(),
            'settings'      => $this->settings,
            'created_at'    => $this->created_at->toIso8601String(),
        ];
    }
}
