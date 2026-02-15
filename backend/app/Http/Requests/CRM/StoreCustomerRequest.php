<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\CRM\Models\Customer::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'type' => ['required', 'in:individual,business'],
            'code' => ['nullable', 'string', 'max:50', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],

            // Business Information
            'company_name' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'employee_count' => ['nullable', 'integer', 'min:0'],
            'established_date' => ['nullable', 'date'],

            // Financial Information
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'payment_method' => ['nullable', 'in:cash,credit_card,bank_transfer,cheque,other'],

            // Status and Classification
            'is_active' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:active,inactive,suspended,blacklisted'],
            'priority' => ['nullable', 'in:low,medium,high,vip'],
            'customer_group' => ['nullable', 'string', 'max:100'],

            // Assignment
            'source' => ['nullable', 'string', 'max:100'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],

            // Additional Information
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'custom_fields' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],

            // Addresses
            'addresses' => ['nullable', 'array'],
            'addresses.*.type' => ['required', 'in:billing,shipping,both,other'],
            'addresses.*.is_primary' => ['nullable', 'boolean'],
            'addresses.*.address_line_1' => ['required', 'string', 'max:255'],
            'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required', 'string', 'max:100'],
            'addresses.*.state' => ['nullable', 'string', 'max:100'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country_id' => ['required', 'integer', 'exists:countries,id'],

            // Contacts
            'contacts' => ['nullable', 'array'],
            'contacts.*.first_name' => ['required', 'string', 'max:100'],
            'contacts.*.last_name' => ['required', 'string', 'max:100'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:20'],
            'contacts.*.designation' => ['nullable', 'string', 'max:100'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required',
            'type.required' => 'Customer type is required',
            'type.in' => 'Invalid customer type',
            'code.unique' => 'This customer code is already in use',
            'email.email' => 'Please provide a valid email address',
            'website.url' => 'Please provide a valid website URL',
        ];
    }
}
