<?php

declare(strict_types=1);

namespace Modules\SystemUser\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListSystemUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'user_id' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'status' => ['nullable', 'string', 'in:active,inactive,blocked'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:' . (int) config('system-user.pagination.max_per_page', 200),
            ],
        ];
    }
}
