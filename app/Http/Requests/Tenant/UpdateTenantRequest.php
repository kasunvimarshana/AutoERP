<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('tenants.update') ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:100', "unique:tenants,slug,{$tenantId}", 'regex:/^[a-z0-9\-]+$/'],
            'domain' => ['sometimes', 'nullable', 'string', 'max:255', "unique:tenants,domain,{$tenantId}"],
            'plan' => ['sometimes', 'string', 'max:50'],
            'settings' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
