<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:tenants,slug'],
            'domain'    => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'plan'      => ['nullable', 'string', 'in:free,starter,professional,enterprise'],
            'status'    => ['nullable', 'string', 'in:active,inactive,suspended'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'settings'  => ['nullable', 'array'],
            'metadata'  => ['nullable', 'array'],
        ];
    }
}
