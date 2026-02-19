<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use App\Http\Requests\ApiRequest;

/**
 * LoginRequest
 *
 * Validates user login credentials and device information
 */
class LoginRequest extends ApiRequest
{
    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'device_id' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:255'],
            'device_type' => ['required', 'string', 'in:mobile,tablet,desktop,web'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
        ];
    }

    /**
     * Get custom messages
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'device_id.required' => 'Device ID is required',
            'device_name.required' => 'Device name is required',
            'device_type.required' => 'Device type is required',
            'device_type.in' => 'Device type must be one of: mobile, tablet, desktop, web',
            'organization_id.uuid' => 'Organization ID must be a valid UUID',
            'organization_id.exists' => 'The selected organization does not exist',
        ];
    }
}
