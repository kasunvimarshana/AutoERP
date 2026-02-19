<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * StoreTenantRequest
 *
 * Validates tenant creation requests
 */
class StoreTenantRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \Modules\Tenant\Models\Tenant::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('tenants', 'slug')->whereNull('deleted_at'),
            ],
            'domain' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')->whereNull('deleted_at'),
            ],
            'settings' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors
     */
    public function attributes(): array
    {
        return [
            'name' => 'tenant name',
            'slug' => 'tenant slug',
            'domain' => 'tenant domain',
            'settings' => 'tenant settings',
            'is_active' => 'active status',
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'This tenant slug is already in use.',
            'domain.unique' => 'This domain is already assigned to another tenant.',
            'slug.alpha_dash' => 'The tenant slug may only contain letters, numbers, dashes and underscores.',
        ];
    }
}
