<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchProductCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'term' => 'nullable|string|max:255',
            'workflow_context' => 'nullable|in:buy,sell,pos',
            'pricing_type' => 'nullable|in:purchase,sales',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'brand_id' => 'nullable|integer|exists:product_brands,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'product_type' => 'nullable|in:physical,service,digital,combo,variable',
            'stock_status' => 'nullable|in:in_stock,out_of_stock,low_stock',
            'include_inactive' => 'nullable|boolean',
            'include_pricing' => 'nullable|boolean',
            'quantity' => 'nullable|numeric|gt:0',
            'price_date' => 'nullable|date',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'per_page' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|in:name:asc,name:desc,sku:asc,sku:desc,available_quantity:asc,available_quantity:desc,updated_at:asc,updated_at:desc',
        ];
    }
}
