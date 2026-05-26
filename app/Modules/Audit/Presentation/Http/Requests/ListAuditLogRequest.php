<?php

declare(strict_types=1);

namespace Modules\Audit\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListAuditLogRequest extends FormRequest
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
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('audit.pagination.max_per_page', 200)],
            'row_version' => ['nullable', 'integer', 'min:0'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'event' => ['nullable', 'string', 'max:255'],
            'auditable_type' => ['nullable', 'string', 'max:255'],
            'auditable_id' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:255'],
        ];
    }
}