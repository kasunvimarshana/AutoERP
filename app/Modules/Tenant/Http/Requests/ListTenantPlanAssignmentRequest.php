<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Modules\Core\Http\Requests\QueryRequest;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Constants\TenantSubscriptionStatus;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionPolicy;

final class ListTenantPlanAssignmentRequest extends QueryRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:'.implode(',', TenantStatus::values())],
            'subscription_effective_status' => ['nullable', 'string', 'in:'.implode(',', [
                TenantSubscriptionPolicy::SCHEDULED,
                TenantSubscriptionStatus::TRIAL,
                TenantSubscriptionStatus::ACTIVE,
                TenantSubscriptionStatus::EXPIRED,
                TenantSubscriptionStatus::CANCELLED,
            ])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
