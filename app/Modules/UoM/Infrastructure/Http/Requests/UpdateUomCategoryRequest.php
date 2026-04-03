<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUomCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|required|string|max:255',
            'code'        => 'sometimes|required|string|max:50',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ];
    }
}
