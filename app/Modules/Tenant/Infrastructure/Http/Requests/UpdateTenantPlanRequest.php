<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $planId = $this->route('plan');

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:127|unique:tenant_plans,slug,'.$planId,
            'features' => 'sometimes|nullable|array',
            'limits' => 'sometimes|nullable|array',
            'price' => 'sometimes|required|numeric|min:0',
            'currency_code' => 'sometimes|required|string|size:3',
            'billing_interval' => 'sometimes|required|in:month,year',
            'is_active' => 'sometimes|required|boolean',
        ];
    }
}
