<?php

declare(strict_types=1);

namespace Modules\SystemUser\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSystemUserRequest extends FormRequest
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
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'user_id' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'code' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive,blocked'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', 'min:1'],
            'updated_by' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
