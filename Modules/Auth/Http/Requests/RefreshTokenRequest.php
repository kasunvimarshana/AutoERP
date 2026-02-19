<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use App\Http\Requests\ApiRequest;

/**
 * RefreshTokenRequest
 *
 * Validates token refresh requests
 */
class RefreshTokenRequest extends ApiRequest
{
    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Token is required',
            'token.string' => 'Token must be a string',
        ];
    }
}
