<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Uses StockLedgerPolicy to enforce fine-grained authorization for stock operations.
     */
    public function authorize(): bool
    {
        // Check if user has permission to perform stock adjustments
        return $this->user()->can('stockAdjustment', \App\Modules\Inventory\Models\StockLedger::class);
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
            'target_balance' => ['required', 'numeric', 'min:0', 'max:999999999.9999'],
            
            // Optional Identification
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            
            // Reference Information
            'reference_type' => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['nullable', 'date', 'before_or_equal:today'],
            
            // Cost Information
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'valuation_method' => ['nullable', 'in:fifo,fefo,lifo,average'],
            
            // Additional Information
            'reason' => ['nullable', 'string', 'in:count,damage,expiry,theft,loss,found,correction,other'],
            'notes' => ['required', 'string', 'max:1000'],
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
            'target_balance.required' => 'Target balance is required',
            'target_balance.min' => 'Target balance cannot be negative',
            'target_balance.max' => 'Target balance exceeds maximum allowed value',
            'variant_id.exists' => 'Selected product variant does not exist',
            'branch_id.exists' => 'Selected branch does not exist',
            'location_id.exists' => 'Selected location does not exist',
            'unit_id.exists' => 'Selected unit of measure does not exist',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future',
            'unit_cost.min' => 'Unit cost cannot be negative',
            'valuation_method.in' => 'Invalid valuation method',
            'reason.in' => 'Invalid adjustment reason',
            'notes.required' => 'Notes are required for stock adjustments',
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

        // Set default reason if not provided
        if (!$this->has('reason')) {
            $this->merge(['reason' => 'correction']);
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
            // Get current balance
            $currentBalance = \App\Modules\Inventory\Models\StockLedger::getCurrentBalance(
                $this->product_id,
                $this->branch_id,
                $this->location_id,
                $this->variant_id
            );
            
            // Add current balance to metadata for logging
            $metadata = $this->input('metadata', []);
            $metadata['previous_balance'] = $currentBalance;
            $metadata['adjustment_quantity'] = $this->target_balance - $currentBalance;
            $this->merge(['metadata' => $metadata]);
        });
    }
}
