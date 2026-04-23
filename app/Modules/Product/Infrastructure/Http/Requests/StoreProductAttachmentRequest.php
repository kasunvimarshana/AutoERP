<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer',
            'file_name' => 'required|string|max:255',
            'file_path' => 'required|string|max:1000',
            'file_type' => 'required|string|max:100',
            'file_size' => 'required|integer|min:0',
            'type' => 'nullable|string|max:50',
            'is_primary' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
