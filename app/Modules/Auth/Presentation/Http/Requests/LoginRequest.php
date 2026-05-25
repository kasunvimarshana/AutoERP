<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'provider_key' => ['nullable', 'string', 'max:120'],
            'login_identifier' => ['required', 'string', 'max:320'],
            'password' => ['required', 'string', 'min:1'],
            'ip_address' => ['nullable', 'ip'],
            'user_agent' => ['nullable', 'string', 'max:1024'],
            'device_name' => ['nullable', 'string', 'max:160'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
