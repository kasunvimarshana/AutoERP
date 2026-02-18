<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_identifier' => $this->uuid,
            'organization_name' => $this->name,
            'primary_domain' => $this->domain,
            'subscription_details' => $this->buildSubscriptionDetails(),
            'account_status' => $this->status,
            'configuration' => $this->when(
                $request->user()?->hasPermissionTo('tenant.view_settings'),
                fn() => $this->settings
            ),
            'trial_information' => $this->buildTrialInfo(),
            'timestamps' => [
                'onboarded' => $this->created_at->toIso8601String(),
                'last_modified' => $this->updated_at->toIso8601String(),
                'deleted' => $this->deleted_at?->toIso8601String(),
            ],
        ];
    }

    private function buildSubscriptionDetails(): array
    {
        $details = [
            'plan_name' => $this->plan ?? 'free',
            'subscription_active' => $this->isSubscriptionActive(),
        ];

        if ($this->subscription_ends_at) {
            $details['expires_at'] = $this->subscription_ends_at->toIso8601String();
            $details['days_remaining'] = now()->diffInDays($this->subscription_ends_at, false);
        }

        return $details;
    }

    private function buildTrialInfo(): ?array
    {
        if (!$this->trial_ends_at) {
            return null;
        }

        return [
            'trial_active' => $this->trial_ends_at->isFuture(),
            'trial_expires' => $this->trial_ends_at->toIso8601String(),
            'trial_days_left' => max(0, now()->diffInDays($this->trial_ends_at, false)),
        ];
    }

    private function isSubscriptionActive(): bool
    {
        if (!$this->subscription_ends_at) {
            return $this->plan === 'free';
        }

        return $this->subscription_ends_at->isFuture();
    }
}
