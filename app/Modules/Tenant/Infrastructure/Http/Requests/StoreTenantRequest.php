<?php
namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'max:255'],
            'slug'   => ['required', 'string', 'max:100', 'unique:tenants,slug'],
            'email'  => ['required', 'email', 'unique:tenants,email'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended,trial'],
            'plan'   => ['nullable', 'string', 'max:50'],
        ];
    }
}
