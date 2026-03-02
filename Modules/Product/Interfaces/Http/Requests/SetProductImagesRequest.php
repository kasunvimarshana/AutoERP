<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetProductImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*.image_path' => ['required', 'string', 'max:1000'],
            'images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'images.*.is_primary' => ['nullable', 'boolean'],
        ];
    }
}
