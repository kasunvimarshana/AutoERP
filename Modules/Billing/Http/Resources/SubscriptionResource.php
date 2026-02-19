<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'plan_id' => $this->plan_id,
            'user_id' => $this->user_id,
            'subscription_code' => $this->subscription_code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'amount' => $this->amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'current_period_start' => $this->current_period_start?->toISOString(),
            'current_period_end' => $this->current_period_end?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'suspended_at' => $this->suspended_at?->toISOString(),
            'is_active' => $this->isActive(),
            'is_trial' => $this->isTrial(),
            'on_trial' => $this->onTrial(),
            'metadata' => $this->metadata,
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization?->id,
                'name' => $this->organization?->name,
            ]),
            'payments' => SubscriptionPaymentResource::collection($this->whenLoaded('payments')),
            'usages' => SubscriptionUsageResource::collection($this->whenLoaded('usages')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
