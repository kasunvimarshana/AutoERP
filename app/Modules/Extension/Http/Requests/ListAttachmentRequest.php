<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListAttachmentRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('extension.pagination.max_per_page', 200)],
            'attachable_type' => ['nullable', 'string', 'max:100'],
            'attachable_id' => ['nullable', 'integer', 'min:1'],
            'source_module' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', 'in:public,private,restricted'],
            'search' => ['nullable', 'string', 'max:255'],
            'include_versions' => ['nullable', 'boolean'],
        ];
    }
}
