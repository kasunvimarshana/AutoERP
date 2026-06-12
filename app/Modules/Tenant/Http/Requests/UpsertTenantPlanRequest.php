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

    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'name' => array_merge($required, ['string', 'max:255']),
            'slug' => array_merge($required, ['string', 'max:255']),
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
            'price' => ['nullable', 'decimal:0,6', 'gte:0'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'billing_interval' => ['nullable', 'string', 'in:month,year'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
