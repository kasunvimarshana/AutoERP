<?php

declare(strict_types=1);

namespace Modules\Extension\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListCommentRequest extends FormRequest
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
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('extension.pagination.max_per_page', 200)],
            'commentable_type' => ['nullable', 'string', 'max:255'],
            'commentable_id' => ['nullable', 'integer', 'min:1'],
            'author_id' => ['nullable', 'integer', 'min:1', 'exists:users,id']
        ];
    }
}