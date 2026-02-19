<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Services\TenantContext;

/**
 * UpdateOrganizationRequest
 *
 * Validates organization update requests
 */
class UpdateOrganizationRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('organization')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        $tenantContext = app(TenantContext::class);
        $tenantId = $tenantContext->getCurrentTenantId();
        $organizationId = $this->route('organization')?->id;

        $organizationTypes = array_keys(config('tenant.organizations.types', []));

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('organizations', 'code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($organizationId)
                    ->whereNull('deleted_at'),
            ],
            'type' => ['sometimes', 'required', 'string', Rule::in($organizationTypes)],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors
     */
    public function attributes(): array
    {
        return [
            'name' => 'organization name',
            'code' => 'organization code',
            'type' => 'organization type',
            'metadata' => 'metadata',
            'is_active' => 'active status',
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'This organization code is already in use within this tenant.',
            'code.alpha_dash' => 'The organization code may only contain letters, numbers, dashes and underscores.',
            'type.in' => 'The selected organization type is invalid.',
        ];
    }
}
