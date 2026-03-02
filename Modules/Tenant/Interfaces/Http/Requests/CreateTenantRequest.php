<?php

declare(strict_types=1);

namespace Modules\Tenant\Interfaces\Http\Requests;

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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'unique:tenants,slug', 'alpha_dash'],
            'plan_code' => ['nullable', 'string', 'max:100'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'currency' => ['nullable', 'string', 'size:3', 'in:'.implode(',', config('currency.supported', ['LKR']))],
        ];
    }
}
