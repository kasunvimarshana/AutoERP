<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validated tenant-creation payload.
 */
final class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized at the route/policy level
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'slug'     => ['sometimes', 'string', 'max:100', 'unique:tenants,slug', 'alpha_dash'],
            'plan'     => ['sometimes', 'string', 'in:free,starter,professional,enterprise'],
            'settings' => ['sometimes', 'array'],
        ];
    }
}
