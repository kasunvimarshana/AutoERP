<?php

declare(strict_types=1);

namespace Modules\Customer\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Customer Request
 *
 * Validates data for creating a new customer
 */
class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_number' => ['sometimes', 'string', 'max:50', 'unique:customers,customer_number'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'blocked'])],
            'customer_type' => ['sometimes', Rule::in(['individual', 'business'])],
            'company_name' => ['nullable', 'string', 'max:255', 'required_if:customer_type,business'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'receive_notifications' => ['sometimes', 'boolean'],
            'receive_marketing' => ['sometimes', 'boolean'],
            'last_service_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'customer_number' => 'customer number',
            'address_line_1' => 'address line 1',
            'address_line_2' => 'address line 2',
            'postal_code' => 'postal code',
            'customer_type' => 'customer type',
            'company_name' => 'company name',
            'tax_id' => 'tax ID',
            'receive_notifications' => 'receive notifications',
            'receive_marketing' => 'receive marketing',
            'last_service_date' => 'last service date',
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
            'email.unique' => 'This email address is already registered.',
            'customer_number.unique' => 'This customer number is already in use.',
            'company_name.required_if' => 'Company name is required for business customers.',
        ];
    }
}
