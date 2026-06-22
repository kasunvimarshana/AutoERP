<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $required = $creating ? ['required'] : ['sometimes'];

        return [
            'expected_version' => $creating
                ? ['prohibited']
                : ['required', 'integer', 'min:1'],
            'code' => [...$required, 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'],
            'name' => [...$required, 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cross_org_transactions' => ['sometimes', 'boolean'],
            'tenant_plan_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('tenant_plans', 'id')->where('is_active', true),
            ],
            'base_currency_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('currencies', 'id')->where('is_active', true),
            ],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
        ];
    }
}
