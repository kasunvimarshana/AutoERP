<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StockTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Uses StockLedgerPolicy to enforce fine-grained authorization for stock operations.
     */
    public function authorize(): bool
    {
        // Check if user has permission to perform stock transfers
        return $this->user()->can('stockTransfer', \App\Modules\Inventory\Models\StockLedger::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Required Fields
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999999.9999'],
            
            // Source Location (FROM)
            'from_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'from_location_id' => ['required', 'integer', 'exists:locations,id'],
            
            // Destination Location (TO)
            'to_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'to_location_id' => ['required', 'integer', 'exists:locations,id', 'different:from_location_id'],
            
            // Optional Identification
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            
            // Reference Information
            'reference_type' => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['nullable', 'date', 'before_or_equal:today'],
            
            // Batch/Serial/Lot Tracking
            'batch_number' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'lot_number' => ['nullable', 'string', 'max:255'],
            
            // Cost Information
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'total_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'valuation_method' => ['nullable', 'in:fifo,fefo,lifo,average'],
            
            // Additional Information
            'notes' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required',
            'product_id.exists' => 'Selected product does not exist',
            'quantity.required' => 'Quantity is required',
            'quantity.min' => 'Quantity must be greater than 0',
            'quantity.max' => 'Quantity exceeds maximum allowed value',
            'from_location_id.required' => 'Source location is required',
            'from_location_id.exists' => 'Source location does not exist',
            'to_location_id.required' => 'Destination location is required',
            'to_location_id.exists' => 'Destination location does not exist',
            'to_location_id.different' => 'Source and destination locations must be different',
            'from_branch_id.exists' => 'Source branch does not exist',
            'to_branch_id.exists' => 'Destination branch does not exist',
            'variant_id.exists' => 'Selected product variant does not exist',
            'unit_id.exists' => 'Selected unit of measure does not exist',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future',
            'unit_cost.min' => 'Unit cost cannot be negative',
            'total_cost.min' => 'Total cost cannot be negative',
            'valuation_method.in' => 'Invalid valuation method',
            'notes.max' => 'Notes cannot exceed 1000 characters',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default transaction date to today if not provided
        if (!$this->has('transaction_date')) {
            $this->merge(['transaction_date' => now()->toDateString()]);
        }

        // Set default valuation method if not provided
        if (!$this->has('valuation_method')) {
            $this->merge(['valuation_method' => 'fifo']);
        }
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if sufficient stock is available at source location
            $currentBalance = \App\Modules\Inventory\Models\StockLedger::getCurrentBalance(
                $this->product_id,
                $this->from_branch_id,
                $this->from_location_id,
                $this->variant_id
            );
            
            if ($currentBalance < $this->quantity) {
                $validator->errors()->add('quantity', 'Insufficient stock at source location. Available: ' . $currentBalance);
            }

            // Validate that serial number exists at source if provided
            if ($this->filled('serial_number')) {
                $exists = \App\Modules\Inventory\Models\StockLedger::where('serial_number', $this->serial_number)
                    ->where('product_id', $this->product_id)
                    ->where('location_id', $this->from_location_id)
                    ->where('balance_quantity', '>', 0)
                    ->exists();
                    
                if (!$exists) {
                    $validator->errors()->add('serial_number', 'Serial number not found at source location');
                }
            }

            // Ensure branches are consistent if both provided
            if ($this->filled('from_branch_id') && $this->filled('to_branch_id')) {
                // Validate that from_location belongs to from_branch
                $fromLocationValid = \App\Modules\Tenant\Models\Location::where('id', $this->from_location_id)
                    ->where('branch_id', $this->from_branch_id)
                    ->exists();
                    
                if (!$fromLocationValid) {
                    $validator->errors()->add('from_location_id', 'Source location does not belong to source branch');
                }

                // Validate that to_location belongs to to_branch
                $toLocationValid = \App\Modules\Tenant\Models\Location::where('id', $this->to_location_id)
                    ->where('branch_id', $this->to_branch_id)
                    ->exists();
                    
                if (!$toLocationValid) {
                    $validator->errors()->add('to_location_id', 'Destination location does not belong to destination branch');
                }
            }
        });
    }
}
