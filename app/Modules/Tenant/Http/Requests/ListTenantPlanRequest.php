<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListTenantPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'is_active' => ['nullable', 'boolean'],
            'billing_interval' => ['nullable', 'string', 'in:month,year'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
