<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'payment_code' => $this->payment_code,
            'amount' => $this->amount,
            'refunded_amount' => $this->refunded_amount,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'payment_method' => $this->payment_method,
            'payment_gateway' => $this->payment_gateway,
            'transaction_id' => $this->transaction_id,
            'error_message' => $this->error_message,
            'paid_at' => $this->paid_at?->toISOString(),
            'is_successful' => $this->isSuccessful(),
            'is_failed' => $this->isFailed(),
            'is_refunded' => $this->isRefunded(),
            'metadata' => $this->metadata,
            'subscription' => $this->whenLoaded('subscription', fn () => [
                'id' => $this->subscription->id,
                'subscription_code' => $this->subscription->subscription_code,
                'status' => $this->subscription->status->value,
                'plan' => new PlanResource($this->subscription->whenLoaded('plan')),
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
