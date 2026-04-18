<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListAuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|min:1',
            'user_id' => 'nullable|integer|min:1',
            'event' => 'nullable|string|max:50',
            'auditable_type' => 'nullable|string|max:255',
            'auditable_id' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
        ];
    }
}
