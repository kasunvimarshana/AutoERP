<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a multipart image file upload for a single product image.
 * Use the JSON-based SetProductImagesRequest for URL-sourced images.
 */
class UploadProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'max:10240'], // max 10 MB
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
