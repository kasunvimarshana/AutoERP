<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'from_branch_id' => ['required', 'string', 'exists:organizations,id'],
            'to_branch_id' => ['required', 'string', 'exists:organizations,id', 'different:from_branch_id'],
            'product_variant_id' => ['required', 'string', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'reference_type' => ['nullable', 'string', 'max:50'],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'lot_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'string', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant ID is required',
            'tenant_id.exists' => 'The selected tenant does not exist',
            'from_branch_id.required' => 'Source branch ID is required',
            'from_branch_id.exists' => 'The selected source branch does not exist',
            'to_branch_id.required' => 'Destination branch ID is required',
            'to_branch_id.exists' => 'The selected destination branch does not exist',
            'to_branch_id.different' => 'Destination branch must be different from source branch',
            'product_variant_id.required' => 'Product variant ID is required',
            'product_variant_id.exists' => 'The selected product variant does not exist',
            'quantity.required' => 'Quantity is required',
            'quantity.integer' => 'Quantity must be an integer',
            'quantity.min' => 'Quantity must be at least 1',
            'unit_cost.required' => 'Unit cost is required',
            'unit_cost.min' => 'Unit cost must be at least 0',
        ];
    }
}
