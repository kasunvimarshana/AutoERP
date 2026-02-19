<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateTenantRequest
 *
 * Validates tenant update requests
 */
class UpdateTenantRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('tenant')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'domain' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')
                    ->ignore($tenantId)
                    ->whereNull('deleted_at'),
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
            'domain.unique' => 'This domain is already assigned to another tenant.',
        ];
    }
}
