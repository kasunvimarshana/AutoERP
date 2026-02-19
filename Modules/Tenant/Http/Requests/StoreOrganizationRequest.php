<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Services\TenantContext;

/**
 * StoreOrganizationRequest
 *
 * Validates organization creation requests
 */
class StoreOrganizationRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Organization::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        $tenantContext = app(TenantContext::class);
        $tenantId = $tenantContext->getCurrentTenantId();

        $organizationTypes = array_keys(config('tenant.organizations.types', []));

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('organizations', 'code')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', 'string', Rule::in($organizationTypes)],
            'parent_id' => [
                'nullable',
                'string',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Perform additional validation
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('parent_id') && $this->input('parent_id')) {
                $this->validateHierarchyDepth($validator);
                $this->validateNoCircularReference($validator);
            }
        });
    }

    /**
     * Validate organization hierarchy depth
     */
    protected function validateHierarchyDepth($validator): void
    {
        $parentId = $this->input('parent_id');
        $maxDepth = config('tenant.organizations.max_depth', 10);

        $parent = Organization::find($parentId);
        if ($parent && $parent->level >= $maxDepth) {
            $validator->errors()->add(
                'parent_id',
                "Cannot create organization. Maximum hierarchy depth of {$maxDepth} levels would be exceeded."
            );
        }
    }

    /**
     * Validate no circular reference (not applicable for new org, but good practice)
     */
    protected function validateNoCircularReference($validator): void
    {
        // For new organizations, this is not an issue, but we validate parent exists
        // The actual circular reference check is more important during moves
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
            'parent_id' => 'parent organization',
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
            'parent_id.exists' => 'The selected parent organization does not exist.',
        ];
    }
}
