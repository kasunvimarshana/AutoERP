<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Tenant Config Request
 */
class UpdateTenantConfigRequest extends FormRequest
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
            'group' => ['nullable', 'string', 'max:100'],
            'config' => ['required', 'array'],
            'config.*' => ['nullable', 'string'],
        ];
    }
}
