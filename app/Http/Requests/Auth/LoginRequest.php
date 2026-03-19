<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'email', 'max:254'],
            'password'    => ['required', 'string', 'min:8'],
            'tenant_id'   => ['nullable', 'uuid'],
            'device_id'   => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'in:mobile,desktop,tablet,unknown'],
            'platform'    => ['nullable', 'string', 'max:100'],
        ];
    }

    public function getDeviceInfo(): array
    {
        return [
            'device_id'   => $this->string('device_id')->value() ?: null,
            'device_name' => $this->string('device_name')->value() ?: null,
            'device_type' => $this->string('device_type')->value() ?: null,
            'platform'    => $this->string('platform')->value() ?: null,
        ];
    }
}
