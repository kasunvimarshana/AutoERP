<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant');

        return [
            'name'      => ['sometimes', 'string', 'max:255'],
            'slug'      => ['sometimes', 'string', 'max:100', 'alpha_dash', "unique:tenants,slug,{$tenantId}"],
            'domain'    => ['nullable', 'string', 'max:255', "unique:tenants,domain,{$tenantId}"],
            'plan'      => ['sometimes', 'string', 'in:free,starter,professional,enterprise'],
            'status'    => ['sometimes', 'string', 'in:active,inactive,suspended'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'settings'  => ['nullable', 'array'],
            'metadata'  => ['nullable', 'array'],
        ];
    }
}
