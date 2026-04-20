<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:127|unique:tenant_plans,slug',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'price' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'billing_interval' => 'required|in:month,year',
            'is_active' => 'required|boolean',
        ];
    }
}
