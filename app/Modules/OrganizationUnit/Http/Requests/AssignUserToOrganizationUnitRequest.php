<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AssignUserToOrganizationUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1'],
            'role_id' => ['nullable', 'integer', 'min:1'],
            'is_default' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
