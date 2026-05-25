<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Tenant\Domain\Constants\TenantStatus;

final class ListTenantRequest extends FormRequest
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
            'status' => ['nullable', 'string', 'in:' . implode(',', TenantStatus::values())],
            'is_active' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
