<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\Procurement\Models\Vendor::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'type' => ['required', 'in:individual,business'],
            'code' => ['nullable', 'string', 'max:50', 'unique:vendors,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],

            // Business Information
            'company_name' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'established_date' => ['nullable', 'date'],

            // Financial Information
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'payment_terms_type' => ['nullable', 'in:net,eom,cod,advance'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'payment_method' => ['nullable', 'in:cash,credit_card,bank_transfer,cheque,other'],

            // Banking Information
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'swift_code' => ['nullable', 'string', 'max:20'],

            // Status and Classification
            'is_active' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:active,inactive,suspended,blacklisted'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'vendor_category' => ['nullable', 'string', 'max:100'],

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
            'name.required' => 'Vendor name is required',
            'type.required' => 'Vendor type is required',
            'type.in' => 'Invalid vendor type',
            'code.unique' => 'This vendor code is already in use',
            'email.email' => 'Please provide a valid email address',
            'website.url' => 'Please provide a valid website URL',
        ];
    }
}
