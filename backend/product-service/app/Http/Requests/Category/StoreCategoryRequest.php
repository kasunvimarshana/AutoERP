<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|max:100',
            'description' => 'sometimes|nullable|string',
            'parent_id' => 'sometimes|nullable|integer',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
