<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use App\Http\Requests\ApiRequest;

/**
 * AttachPermissionsRequest
 *
 * Validates permission attachment requests
 */
class AttachPermissionsRequest extends ApiRequest
{
    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        return [
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ];
    }

    /**
     * Get custom messages
     */
    public function messages(): array
    {
        return [
            'permission_ids.required' => 'Permission IDs are required',
            'permission_ids.array' => 'Permission IDs must be an array',
            'permission_ids.min' => 'At least one permission ID is required',
            'permission_ids.*.uuid' => 'Each permission ID must be a valid UUID',
            'permission_ids.*.exists' => 'One or more selected permissions do not exist',
        ];
    }
}
