<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Services\TenantContext;

/**
 * MoveOrganizationRequest
 *
 * Validates organization move requests
 */
class MoveOrganizationRequest extends ApiRequest
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

        return [
            'parent_id' => [
                'nullable',
                'string',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * Perform additional validation
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $organization = $this->route('organization');
            $newParentId = $this->input('parent_id');

            if ($newParentId) {
                $this->validateNotMovingToSelf($validator, $organization, $newParentId);
                $this->validateNotMovingToDescendant($validator, $organization, $newParentId);
                $this->validateHierarchyDepth($validator, $organization, $newParentId);
            }
        });
    }

    /**
     * Validate not moving to self
     */
    protected function validateNotMovingToSelf($validator, Organization $organization, string $newParentId): void
    {
        if ($organization->id === $newParentId) {
            $validator->errors()->add(
                'parent_id',
                'Cannot move organization to itself.'
            );
        }
    }

    /**
     * Validate not moving to a descendant
     */
    protected function validateNotMovingToDescendant($validator, Organization $organization, string $newParentId): void
    {
        $descendants = $organization->descendants();

        if ($descendants->contains('id', $newParentId)) {
            $validator->errors()->add(
                'parent_id',
                'Cannot move organization to one of its descendants. This would create a circular reference.'
            );
        }
    }

    /**
     * Validate organization hierarchy depth after move
     */
    protected function validateHierarchyDepth($validator, Organization $organization, string $newParentId): void
    {
        $maxDepth = config('tenant.organizations.max_depth', 10);
        $newParent = Organization::find($newParentId);

        if ($newParent) {
            $organizationDepth = $organization->descendants()->max('level') ?? $organization->level;
            $organizationHeight = $organizationDepth - $organization->level;
            $newLevel = $newParent->level + 1;
            $resultingMaxDepth = $newLevel + $organizationHeight;

            if ($resultingMaxDepth > $maxDepth) {
                $validator->errors()->add(
                    'parent_id',
                    "Cannot move organization. Maximum hierarchy depth of {$maxDepth} levels would be exceeded."
                );
            }
        }
    }

    /**
     * Get custom attributes for validator errors
     */
    public function attributes(): array
    {
        return [
            'parent_id' => 'parent organization',
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'parent_id.exists' => 'The selected parent organization does not exist.',
        ];
    }
}
