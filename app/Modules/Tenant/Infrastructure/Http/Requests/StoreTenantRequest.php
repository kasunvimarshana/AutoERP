<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'slug'                 => ['required', 'string', 'max:100', 'alpha_dash', 'unique:tenants,slug'],
            'status'               => ['sometimes', 'string', 'in:active,suspended,trial,cancelled'],
            'plan'                 => ['sometimes', 'string', 'in:free,starter,professional,enterprise'],
            'domain'               => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'logo_path'            => ['nullable', 'string', 'max:1000'],
            'settings'             => ['nullable', 'array'],
            'trial_ends_at'        => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'metadata'             => ['nullable', 'array'],
        ];
    }
}
