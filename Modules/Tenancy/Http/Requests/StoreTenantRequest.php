<?php

declare(strict_types=1);

namespace Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Tenant Request
 *
 * Validates data for creating a new tenant.
 */
class StoreTenantRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tenants,slug', 'alpha_dash'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'database' => ['nullable', 'string', 'max:255'],
            'schema' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive,suspended'],
            'settings' => ['nullable', 'array'],
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
            'trial_ends_at' => ['nullable', 'date', 'after:now'],
            'subscription_ends_at' => ['nullable', 'date', 'after:now'],
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
