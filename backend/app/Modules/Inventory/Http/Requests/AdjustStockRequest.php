<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'branch_id' => ['required', 'string', 'exists:organizations,id'],
            'product_variant_id' => ['required', 'string', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'reference_type' => ['nullable', 'string', 'max:50'],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'lot_number' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'string', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant ID is required',
            'tenant_id.exists' => 'The selected tenant does not exist',
            'branch_id.required' => 'Branch ID is required',
            'branch_id.exists' => 'The selected branch does not exist',
            'product_variant_id.required' => 'Product variant ID is required',
            'product_variant_id.exists' => 'The selected product variant does not exist',
            'quantity.required' => 'Quantity is required',
            'quantity.integer' => 'Quantity must be an integer',
            'unit_cost.required' => 'Unit cost is required',
            'unit_cost.min' => 'Unit cost must be at least 0',
            'expiry_date.after' => 'Expiry date must be in the future',
        ];
    }
}
