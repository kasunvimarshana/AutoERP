<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Billing\Enums\SubscriptionStatus;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Billing\Models\Subscription::class);
    }

    public function rules(): array
    {
        return [
            'organization_id' => [
                'nullable',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'plan_id' => [
                'required',
                Rule::exists('billing_plans', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'user_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'subscription_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('subscriptions', 'subscription_code')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'status' => ['nullable', Rule::enum(SubscriptionStatus::class)],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'current_period_start' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
