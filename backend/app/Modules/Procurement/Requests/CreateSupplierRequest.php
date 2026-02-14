<?php

namespace App\Modules\Procurement\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Supplier Request
 * 
 * Validation rules for creating a new supplier
 * 
 * @package App\Modules\Procurement\Requests
 */
class CreateSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:100',
            'status' => 'required|string|in:active,inactive,blocked',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'status.in' => 'The status must be one of: active, inactive, blocked.',
            'email.email' => 'Please provide a valid email address.',
        ];
    }
}
