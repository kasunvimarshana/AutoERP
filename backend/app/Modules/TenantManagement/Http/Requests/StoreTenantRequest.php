<?php

namespace App\Modules\TenantManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policies
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tenants,slug',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'database' => 'nullable|string|max:255',
            'subscription_plan' => 'nullable|string|max:100',
            'max_users' => 'nullable|integer|min:1',
            'max_branches' => 'nullable|integer|min:1',
            'settings' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }
}
