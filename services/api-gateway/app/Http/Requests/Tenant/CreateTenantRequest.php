<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Tenant Request - validates new tenant registration.
 */
class CreateTenantRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'plan' => ['nullable', 'string', 'in:free,starter,professional,enterprise'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
