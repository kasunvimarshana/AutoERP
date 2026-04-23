<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'sometimes|integer|exists:products,id',
            'variant_id' => 'nullable|integer',
            'file_name' => 'sometimes|string|max:255',
            'file_path' => 'sometimes|string|max:1000',
            'file_type' => 'sometimes|string|max:100',
            'file_size' => 'sometimes|integer|min:0',
            'type' => 'nullable|string|max:50',
            'is_primary' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
