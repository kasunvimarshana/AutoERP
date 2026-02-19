<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('tenants.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:100', 'unique:tenants,slug', 'regex:/^[a-z0-9\-]+$/'],
            'domain' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'plan' => ['sometimes', 'string', 'max:50'],
            'settings' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
            'trial_ends_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
