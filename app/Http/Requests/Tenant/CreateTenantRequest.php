<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'min:2', 'max:255'],
            'slug'          => ['required', 'string', 'max:100', 'unique:tenants,slug', 'regex:/^[a-z0-9\-]+$/'],
            'domain'        => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'status'        => ['sometimes', 'string', 'in:active,inactive,suspended'],
            'timezone'      => ['sometimes', 'string', 'max:100'],
            'locale'        => ['sometimes', 'string', 'max:10'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'settings'      => ['sometimes', 'nullable', 'array'],
        ];
    }
}
