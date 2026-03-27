<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|regex:/^[a-z0-9\-]+$/',
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|integer|exists:categories,id',
            'status'      => 'nullable|string|in:active,inactive,draft',
            'attributes'  => 'nullable|array',
            'metadata'    => 'nullable|array',
        ];
    }
}
