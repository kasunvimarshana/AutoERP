<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'timezone' => $this->timezone,
            'currency_code' => $this->currency_code,
            'language_code' => $this->language_code,
            'date_format' => $this->date_format,
            'time_format' => $this->time_format,
            'status' => $this->status,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'subscription_plan_id' => $this->subscription_plan_id,
            'subscription_starts_at' => $this->subscription_starts_at?->toIso8601String(),
            'subscription_ends_at' => $this->subscription_ends_at?->toIso8601String(),
            'settings' => $this->settings,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Computed properties
            'is_trial' => $this->status === 'trial',
            'is_active' => $this->status === 'active',
            'is_suspended' => $this->status === 'suspended',
            'has_active_subscription' => $this->subscription_plan_id !== null 
                && $this->subscription_ends_at 
                && $this->subscription_ends_at->isFuture(),
            
            // Related data (when loaded)
            'subscription_plan' => $this->whenLoaded('subscriptionPlan', function () {
                return new SubscriptionPlanResource($this->subscriptionPlan);
            }),
            'organizations' => $this->whenLoaded('organizations', function () {
                return OrganizationResource::collection($this->organizations);
            }),
            'created_by_user' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'updated_by_user' => $this->whenLoaded('updatedBy', function () {
                return [
                    'id' => $this->updatedBy->id,
                    'name' => $this->updatedBy->name,
                    'email' => $this->updatedBy->email,
                ];
            }),
        ];
    }
}
