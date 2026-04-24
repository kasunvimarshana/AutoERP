<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|min:1',
            'q' => 'nullable|string|max:255',
            'identifier' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'rfid' => 'nullable|string|max:255',
            'qr' => 'nullable|string|max:255',
            'batch' => 'nullable|string|max:255',
            'lot' => 'nullable|string|max:255',
            'product_id' => 'nullable|integer|min:1',
            'variant_id' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer|min:1',
            'brand_id' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'in_stock' => 'nullable|boolean',
            'min_available' => 'nullable|numeric',
            'max_available' => 'nullable|numeric',
            'warehouse_id' => 'nullable|integer|min:1',
            'warehouse_in_stock' => 'nullable|boolean',
            'sort' => 'nullable|string|in:relevance,name,-name,stock,-stock,updated',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'include_pricing' => 'nullable|boolean',
            'context_type' => 'nullable|string|in:purchase,sales',
            'currency_id' => 'nullable|integer|min:1',
            'customer_id' => 'nullable|integer|min:1|required_if:context_type,sales',
            'supplier_id' => 'nullable|integer|min:1|required_if:context_type,purchase',
            'price_quantity' => 'nullable|numeric|gt:0',
            'price_uom_id' => 'nullable|integer|min:1',
            'price_date' => 'nullable|date',
        ];
    }
}
