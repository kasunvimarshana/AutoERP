<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->hasRole('super-admin');
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'subdomain'   => [
                'required', 'string', 'max:63', 'alpha_dash',
                'unique:tenants,subdomain',
                'regex:/^[a-z0-9][a-z0-9\-]*[a-z0-9]$/',
            ],
            'plan'        => ['required', 'string', 'in:free,starter,pro,enterprise'],
            'admin_name'  => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
            'settings'    => ['sometimes', 'array'],
            'features'    => ['sometimes', 'array'],
            'config'      => ['sometimes', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'subdomain.regex'     => 'Subdomain must contain only lowercase letters, numbers, and hyphens.',
            'subdomain.unique'    => 'This subdomain is already taken.',
            'admin_email.unique'  => 'This email address is already registered.',
        ];
    }
}
