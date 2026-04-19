<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->input('tenant_id');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'brand_id' => 'nullable|integer|exists:product_brands,id',
            'org_unit_id' => 'nullable|integer|exists:org_units,id',
            'type' => 'required|string|in:physical,service,digital,combo,variable',
            'name' => 'required|string|max:255',
            'image_path' => 'nullable|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'description' => 'nullable|string',
            'base_uom_id' => 'required|integer|exists:units_of_measure,id',
            'purchase_uom_id' => 'nullable|integer|exists:units_of_measure,id',
            'sales_uom_id' => 'nullable|integer|exists:units_of_measure,id',
            'tax_group_id' => 'nullable|integer|exists:tax_groups,id',
            'uom_conversion_factor' => 'nullable|numeric|min:0.0000000001',
            'is_batch_tracked' => 'nullable|boolean',
            'is_lot_tracked' => 'nullable|boolean',
            'is_serial_tracked' => 'nullable|boolean',
            'valuation_method' => 'nullable|string|in:fifo,lifo,fefo,weighted_average,standard',
            'standard_cost' => 'nullable|numeric|min:0',
            'income_account_id' => 'nullable|integer|exists:accounts,id',
            'cogs_account_id' => 'nullable|integer|exists:accounts,id',
            'inventory_account_id' => 'nullable|integer|exists:accounts,id',
            'expense_account_id' => 'nullable|integer|exists:accounts,id',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
