<?php

declare(strict_types=1);

namespace Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Tenant Request
 *
 * Validates data for updating an existing tenant.
 */
class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('tenants', 'slug')->ignore($tenantId),
            ],
            'domain' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')->ignore($tenantId),
            ],
            'database' => ['sometimes', 'nullable', 'string', 'max:255'],
            'schema' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'in:active,inactive,suspended'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'features' => ['sometimes', 'nullable', 'array'],
            'limits' => ['sometimes', 'nullable', 'array'],
            'trial_ends_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            'subscription_ends_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tenant name is required',
            'slug.required' => 'Tenant slug is required',
            'slug.unique' => 'This slug is already taken',
            'slug.alpha_dash' => 'Slug can only contain letters, numbers, dashes, and underscores',
            'domain.unique' => 'This domain is already registered',
            'status.in' => 'Status must be active, inactive, or suspended',
            'trial_ends_at.after' => 'Trial end date must be in the future',
            'subscription_ends_at.after' => 'Subscription end date must be in the future',
        ];
    }
}
