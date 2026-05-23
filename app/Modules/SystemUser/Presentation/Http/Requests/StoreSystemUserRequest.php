<?php

declare(strict_types=1);

namespace Modules\SystemUser\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSystemUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::unique('system_users', 'user_id')->where('tenant_id', $this->route('tenant')),
            ],
            'code' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive,blocked'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer'],
            'updated_by' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
