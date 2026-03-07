<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|max:100|unique:tenants,slug|regex:/^[a-z0-9-]+$/',
            'domain' => 'sometimes|nullable|string|max:255',
            'settings' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
            'plan' => 'sometimes|string|in:basic,pro,enterprise',
        ];
    }
}
