<?php

declare(strict_types=1);

namespace Modules\Customer\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Customer Request
 *
 * Validates data for updating an existing customer
 */
class UpdateCustomerRequest extends FormRequest
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
        $customerId = $this->route('customer'); // Route parameter from apiResource

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('customers')->ignore($customerId)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address_line_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'blocked'])],
            'customer_type' => ['sometimes', Rule::in(['individual', 'business'])],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255', 'required_if:customer_type,business'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'receive_notifications' => ['sometimes', 'boolean'],
            'receive_marketing' => ['sometimes', 'boolean'],
            'last_service_date' => ['sometimes', 'nullable', 'date'],
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
            'company_name.required_if' => 'Company name is required for business customers.',
        ];
    }
}
