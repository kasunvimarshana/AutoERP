<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'price' => $this->price,
            'interval' => $this->interval->value,
            'interval_label' => $this->interval->label(),
            'interval_count' => $this->interval_count,
            'trial_days' => $this->trial_days,
            'features' => $this->features,
            'limits' => $this->limits,
            'user_limit' => $this->user_limit,
            'storage_limit_gb' => $this->storage_limit_gb,
            'is_active' => $this->is_active,
            'is_public' => $this->is_public,
            'sort_order' => $this->sort_order,
            'subscriptions_count' => $this->whenCounted('subscriptions'),
            'active_subscriptions_count' => $this->whenCounted('activeSubscriptions'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
