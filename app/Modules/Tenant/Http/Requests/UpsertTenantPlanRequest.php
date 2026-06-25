<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
            'price' => ['nullable', 'decimal:0,6', 'gte:0'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'billing_interval' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'in:month,quarter,year',
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
