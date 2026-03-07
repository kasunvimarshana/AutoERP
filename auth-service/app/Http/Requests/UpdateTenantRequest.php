<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validated tenant-update payload.
 */
final class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'string', 'max:255'],
            'plan'      => ['sometimes', 'string', 'in:free,starter,professional,enterprise'],
            'is_active' => ['sometimes', 'boolean'],
            'settings'  => ['sometimes', 'array'],
        ];
    }
}
