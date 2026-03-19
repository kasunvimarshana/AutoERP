<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenantId');

        return [
            'name'          => ['sometimes', 'string', 'min:2', 'max:255'],
            'slug'          => ['sometimes', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', "unique:tenants,slug,{$tenantId},id"],
            'domain'        => ['sometimes', 'nullable', 'string', 'max:255', "unique:tenants,domain,{$tenantId},id"],
            'status'        => ['sometimes', 'string', 'in:active,inactive,suspended'],
            'timezone'      => ['sometimes', 'string', 'max:100'],
            'locale'        => ['sometimes', 'string', 'max:10'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'settings'      => ['sometimes', 'nullable', 'array'],
        ];
    }
}
