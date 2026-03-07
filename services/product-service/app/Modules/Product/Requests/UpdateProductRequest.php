<?php

namespace App\Modules\Product\Requests;

use App\Modules\Product\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id');
        return [
            'name' => 'sometimes|string|max:255',
            'sku' => "sometimes|string|max:100|unique:products,sku,{$productId}",
            'price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string|max:5000',
            'category' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:' . implode(',', Product::STATUSES),
            'metadata' => 'nullable|array',
        ];
    }
}
