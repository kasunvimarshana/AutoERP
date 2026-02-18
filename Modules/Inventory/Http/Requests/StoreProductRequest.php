<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'unit_of_measure_id' => ['required', 'integer', 'exists:unit_of_measures,id'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'product_type' => ['required', 'in:stockable,consumable,service'],
            'status' => ['required', 'in:active,inactive,discontinued'],
            'list_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'dimension_unit' => ['nullable', 'in:cm,m,in,ft'],
            'weight_unit' => ['nullable', 'in:g,kg,oz,lb'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'reorder_quantity' => ['nullable', 'integer', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'shelf_life_days' => ['nullable', 'integer', 'min:0'],
            'is_serialized' => ['boolean'],
            'is_batch_tracked' => ['boolean'],
            'image_url' => ['nullable', 'url'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
