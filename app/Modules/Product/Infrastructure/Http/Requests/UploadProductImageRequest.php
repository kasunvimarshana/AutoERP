<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'       => 'required|file|image|max:10240',
            'sort_order' => 'nullable|integer|min:0',
            'is_primary' => 'nullable|boolean',
            'metadata'   => 'nullable|string',
        ];
    }
}
