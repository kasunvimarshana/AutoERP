<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:320'],
            'password' => ['required', 'string', 'max:1024'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'device_name' => ['nullable', 'string', 'max:160'],
            'tenant_id' => ['prohibited'],
            'tenant_code' => ['prohibited'],
            'user_id' => ['prohibited'],
            'ip_address' => ['prohibited'],
            'user_agent' => ['prohibited'],
        ];
    }
}
