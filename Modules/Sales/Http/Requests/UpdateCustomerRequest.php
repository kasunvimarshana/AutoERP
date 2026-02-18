<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Customer Request
 *
 * Validates data for updating an existing customer.
 */
class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $this->user()->can('update', $customer);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customerId = $this->route('customer');

        return [
            'customer_number' => ['nullable', 'string', 'max:50', "unique:customers,customer_number,{$customerId}"],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:individual,company'],
            'email' => ['sometimes', 'required', 'email', 'max:255', "unique:customers,email,{$customerId}"],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'payment_terms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', 'in:active,inactive,suspended'],
            'tags' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
