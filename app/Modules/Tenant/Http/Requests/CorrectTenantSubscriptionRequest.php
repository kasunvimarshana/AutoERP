<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Constants\TenantSubscriptionStatus;

final class CorrectTenantSubscriptionRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'expected_tenant_version' => ['required', 'integer', 'min:1'],
            'expected_subscription_version' => ['required', 'integer', 'min:1'],
            'tenant_plan_revision_id' => ['required', 'integer', 'min:1', Rule::exists('tenant_plan_revisions', 'id')],
            'contract_status' => ['required', 'string', Rule::in(TenantSubscriptionStatus::assignable())],
            'starts_at' => ['required', 'date', 'before_or_equal:now'],
            'trial_ends_at' => [
                Rule::requiredIf(fn (): bool => $this->input('contract_status') === TenantSubscriptionStatus::TRIAL),
                Rule::prohibitedIf(fn (): bool => $this->input('contract_status') === TenantSubscriptionStatus::ACTIVE),
                'nullable',
                'date',
            ],
            'ends_at' => [
                Rule::prohibitedIf(fn (): bool => $this->input('contract_status') === TenantSubscriptionStatus::TRIAL),
                'nullable',
                'date',
            ],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
