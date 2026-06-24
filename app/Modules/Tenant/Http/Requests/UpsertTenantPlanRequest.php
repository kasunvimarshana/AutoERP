<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Services\Plans\TenantPlanSchema;

final class UpsertTenantPlanRequest extends FormRequest
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
            'name' => [...$required, 'string', 'max:255'],
            'slug' => [...$required, 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'features' => ['nullable', 'array:enabled_modules'],
            'features.enabled_modules' => ['sometimes', 'array'],
            'features.enabled_modules.*' => [
                'string',
                'distinct:strict',
                Rule::in(TenantPlanSchema::SUPPORTED_MODULES),
            ],
            'limits' => ['nullable', 'array:'.implode(',', TenantPlanSchema::SUPPORTED_LIMITS)],
            'limits.max_users' => ['nullable', 'integer', 'min:1'],
            'limits.max_organization_units' => ['nullable', 'integer', 'min:1'],
            'limits.max_warehouses' => ['nullable', 'integer', 'min:1'],
            'limits.max_storage_mb' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'decimal:0,6', 'gte:0'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'billing_interval' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'in:month,quarter,year',
            ],
            'effective_at' => [$creating ? 'nullable' : 'sometimes', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
