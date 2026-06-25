<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Modules\Core\Http\Requests\QueryRequest;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Constants\TenantDomainOperationalStatus;
use Modules\Tenant\Constants\TenantOnboardingStatus;
use Modules\Tenant\Constants\TenantStatus;

final class ListTenantRequest extends QueryRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:'.implode(',', TenantStatus::values())],
            'search' => ['nullable', 'string', 'max:255'],
            'onboarding_status' => ['nullable', 'string', 'in:'.implode(',', TenantOnboardingStatus::values())],
            'domain_operational_status' => ['nullable', 'string', 'in:'.implode(',', TenantDomainOperationalStatus::values())],
            'subscription_state' => ['nullable', 'string', 'in:'.implode(',', TenantCurrentSubscriptionState::values())],
            'plan_id' => ['nullable', 'integer', 'min:1', 'exists:tenant_plans,id'],
            'expires_within_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
