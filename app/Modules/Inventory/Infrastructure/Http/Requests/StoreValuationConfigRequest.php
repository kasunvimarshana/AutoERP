<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreValuationConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'org_unit_id' => 'nullable|integer|exists:org_units,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'transaction_type' => 'nullable|string|max:50',
            'valuation_method' => ['required', Rule::in(['fifo', 'lifo', 'fefo', 'weighted_average', 'standard', 'specific'])],
            'allocation_strategy' => ['required', Rule::in(['fifo', 'lifo', 'fefo', 'nearest_bin', 'manual'])],
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
