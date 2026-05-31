<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListItemTypeRequest extends FormRequest
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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('item.pagination.max_per_page', 200)],
            'search' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'is_stockable' => ['nullable', 'boolean'],
            'is_service' => ['nullable', 'boolean'],
            'is_rentable' => ['nullable', 'boolean'],
            'is_chargeable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
