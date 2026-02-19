<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Billing\Enums\BillingInterval;
use Modules\Billing\Enums\PlanType;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Billing\Models\Plan::class);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('billing_plans', 'code')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(PlanType::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'interval' => ['required', Rule::enum(BillingInterval::class)],
            'interval_count' => ['nullable', 'integer', 'min:1'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
            'storage_limit_gb' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_public' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
