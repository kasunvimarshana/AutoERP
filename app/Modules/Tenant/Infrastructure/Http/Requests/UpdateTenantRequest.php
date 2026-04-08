<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('tenant');

        return [
            'name'                 => ['sometimes', 'string', 'max:255'],
            'slug'                 => ['sometimes', 'string', 'max:100', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($id)],
            'status'               => ['sometimes', 'string', 'in:active,suspended,trial,cancelled'],
            'plan'                 => ['sometimes', 'string', 'in:free,starter,professional,enterprise'],
            'domain'               => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($id)],
            'logo_path'            => ['nullable', 'string', 'max:1000'],
            'settings'             => ['nullable', 'array'],
            'trial_ends_at'        => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'metadata'             => ['nullable', 'array'],
        ];
    }
}
