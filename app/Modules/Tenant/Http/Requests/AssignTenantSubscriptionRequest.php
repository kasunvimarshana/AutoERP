<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Constants\TenantSubscriptionStatus;

final class AssignTenantSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'expected_tenant_version' => ['required', 'integer', 'min:1'],
            'tenant_plan_revision_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('tenant_plan_revisions', 'id'),
            ],
            'status' => ['required', 'string', Rule::in(TenantSubscriptionStatus::assignable())],
            'starts_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
