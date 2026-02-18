<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Organization\Entities\Organization;

/**
 * Move Organization Request
 *
 * Validates organization movement (re-parenting) requests.
 */
class MoveOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $organization = Organization::find($this->route('id'));
        
        if (!$organization) {
            return false;
        }

        return $this->user()->can('move', $organization);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $organizationId = $this->route('id');

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:organizations,id,tenant_id,' . auth()->user()->tenant_id,
                function ($attribute, $value, $fail) use ($organizationId) {
                    // Cannot set self as parent
                    if ($value == $organizationId) {
                        $fail('Organization cannot be its own parent');
                    }

                    // Validate parent belongs to same tenant
                    if ($value) {
                        $parent = Organization::find($value);
                        if ($parent && $parent->tenant_id !== auth()->user()->tenant_id) {
                            $fail('Parent organization must belong to the same tenant');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'parent_id.exists' => 'The selected parent organization does not exist',
            'parent_id.integer' => 'Parent organization ID must be a valid integer',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'parent_id' => 'parent organization',
        ];
    }
}
