<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertUserRoleRequest extends FormRequest
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
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'user_id' => array_merge($required, ['integer', 'min:1', 'exists:users,id']),
            'role_id' => array_merge($required, ['integer', 'min:1', 'exists:roles,id']),
        ];
    }
}
