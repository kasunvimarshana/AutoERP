<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AssignUserToOrganizationUnitRequest extends FormRequest
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
            'organization_unit_id' => ['required', 'integer', 'min:1'],
            'role_id' => ['nullable', 'integer', 'min:1'],
            'is_default' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
