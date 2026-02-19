<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use App\Http\Requests\ApiRequest;

/**
 * RegisterRequest
 *
 * Validates user registration data
 */
class RegisterRequest extends ApiRequest
{
    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'device_id' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:255'],
            'device_type' => ['required', 'string', 'in:mobile,tablet,desktop,web'],
        ];
    }

    /**
     * Get custom messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'organization_id.required' => 'Organization ID is required',
            'organization_id.uuid' => 'Organization ID must be a valid UUID',
            'organization_id.exists' => 'The selected organization does not exist',
            'device_id.required' => 'Device ID is required',
            'device_name.required' => 'Device name is required',
            'device_type.required' => 'Device type is required',
            'device_type.in' => 'Device type must be one of: mobile, tablet, desktop, web',
        ];
    }
}
