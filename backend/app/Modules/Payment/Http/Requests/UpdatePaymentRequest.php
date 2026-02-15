<?php

declare(strict_types=1);

namespace App\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $payment = \App\Modules\Payment\Models\Payment::find($this->route('id'));
        return $payment && $this->user()->can('update', $payment);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $paymentId = $this->route('id');
        
        return [
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'payment_number' => ['nullable', 'string', 'max:255', "unique:payments,payment_number,{$paymentId}"],
            'payment_date' => ['sometimes', 'date'],
            'payment_type' => ['sometimes', 'in:received,paid'],
            'entity_type' => ['nullable', 'string', 'in:App\Modules\CRM\Models\Customer,App\Modules\Procurement\Models\Vendor'],
            'entity_id' => ['nullable', 'integer'],
            'payment_method_id' => ['sometimes', 'integer', 'exists:payment_methods,id'],
            'amount' => ['sometimes', 'numeric', 'min:0.01', 'max:999999999999.99'],
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
            'payment_method_id.exists' => 'Selected payment method is invalid.',
            'amount.min' => 'Payment amount must be at least 0.01.',
        ];
    }
}
