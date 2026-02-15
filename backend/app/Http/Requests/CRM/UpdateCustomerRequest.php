<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

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
     */
    public function rules(): array
    {
        $customerId = $this->route('customer')->id ?? null;

        return [
            // Basic Information
            'type' => ['sometimes', 'in:individual,business'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:customers,code,' . $customerId],
            'name' => ['sometimes', 'string', 'max:255'],
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
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Invalid customer type',
            'code.unique' => 'This customer code is already in use',
            'email.email' => 'Please provide a valid email address',
            'website.url' => 'Please provide a valid website URL',
        ];
    }
}
