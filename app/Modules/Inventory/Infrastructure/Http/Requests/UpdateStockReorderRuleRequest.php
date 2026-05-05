<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockReorderRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'        => 'required|integer|exists:tenants,id',
            'product_id'       => 'nullable|integer|exists:products,id',
            'variant_id'       => 'nullable|integer|exists:product_variants,id',
            'warehouse_id'     => 'nullable|integer|exists:warehouses,id',
            'minimum_quantity' => 'nullable|numeric|min:0',
            'maximum_quantity' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0.000001',
            'is_active'        => 'nullable|boolean',
        ];
    }
}
