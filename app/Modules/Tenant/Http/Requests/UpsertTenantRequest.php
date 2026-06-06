<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Tenant\Constants\TenantStatus;

final class UpsertTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'code' => array_merge($required, ['string', 'max:100']),
            'name' => array_merge($required, ['string', 'max:255']),
            'slug' => ['nullable', 'string', 'max:255'],
            'logo_path' => ['nullable', 'file', 'image', 'max:5120'],
            'cross_org_transactions' => ['nullable', 'boolean'],
            'tenant_plan_id' => ['nullable', 'integer', 'min:1', 'exists:tenant_plans,id'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'status' => ['nullable', 'string', 'in:'.implode(',', TenantStatus::values())],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'is_isolated' => ['nullable', 'boolean'],
            'isolation_key' => ['nullable', 'string', 'max:255'],
            'configuration_scope' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
