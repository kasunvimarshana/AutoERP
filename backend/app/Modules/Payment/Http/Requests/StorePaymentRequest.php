<?php

declare(strict_types=1);

namespace App\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\Payment\Models\Payment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'payment_number' => ['nullable', 'string', 'max:255', 'unique:payments,payment_number'],
            'payment_date' => ['required', 'date'],
            'payment_type' => ['required', 'in:received,paid'],
            'entity_type' => ['nullable', 'string', 'in:App\Modules\CRM\Models\Customer,App\Modules\Procurement\Models\Vendor'],
            'entity_id' => ['nullable', 'integer'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'cheque_number' => ['nullable', 'string', 'max:255'],
            'cheque_date' => ['nullable', 'date'],
            'card_last_four' => ['nullable', 'string', 'size:4'],
            'card_type' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,completed,failed,cancelled'],
            'metadata' => ['nullable', 'array'],
            
            // Allocations
            'allocations' => ['nullable', 'array'],
            'allocations.*.allocatable_type' => ['required_with:allocations', 'string'],
            'allocations.*.allocatable_id' => ['required_with:allocations', 'integer'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0.01'],
            'allocations.*.currency_code' => ['nullable', 'string', 'size:3'],
            'allocations.*.exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'allocations.*.notes' => ['nullable', 'string'],
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
            'payment_method_id.required' => 'Payment method is required.',
            'payment_method_id.exists' => 'Selected payment method is invalid.',
            'amount.required' => 'Payment amount is required.',
            'amount.min' => 'Payment amount must be at least 0.01.',
            'payment_date.required' => 'Payment date is required.',
            'payment_type.required' => 'Payment type is required.',
            'payment_type.in' => 'Payment type must be either received or paid.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $defaults = [
            'payment_date' => $this->payment_date ?? now()->toDateString(),
            'status' => $this->status ?? 'pending',
            'currency_code' => $this->currency_code ?? 'USD',
            'exchange_rate' => $this->exchange_rate ?? 1.0000,
        ];

        $this->merge($defaults);
    }
}
