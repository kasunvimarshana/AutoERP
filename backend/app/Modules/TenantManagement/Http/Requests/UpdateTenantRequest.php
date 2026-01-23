<?php

namespace App\Modules\TenantManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant');

        return [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:tenants,slug,' . $tenantId,
            'domain' => 'nullable|string|max:255|unique:tenants,domain,' . $tenantId,
            'database' => 'nullable|string|max:255',
            'status' => 'sometimes|in:active,suspended,inactive',
            'subscription_plan' => 'nullable|string|max:100',
            'max_users' => 'nullable|integer|min:1',
            'max_branches' => 'nullable|integer|min:1',
            'settings' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }
}
