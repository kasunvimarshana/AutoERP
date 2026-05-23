<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreTenantRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            $this->merge(['slug' => Str::slug((string) $this->input('slug'))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tenants,slug'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'cross_org_transactions' => ['nullable', 'boolean'],
            'tenant_plan_id' => ['nullable', 'integer', 'exists:tenant_plans,id'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'status' => ['nullable', 'string', 'in:active,suspended,pending,cancelled'],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
