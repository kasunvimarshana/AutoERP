<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Customer\DTOs\CreateCustomerData;
use Modules\Customer\Enums\CustomerAddressType;
use Modules\Customer\Enums\CustomerDocumentStatus;
use Modules\Customer\Enums\CustomerDocumentType;
use Modules\Customer\Http\Requests\Concerns\MapsCustomerData;

final class StoreCustomerWithRelationsRequest extends TenantScopedRequest
{
    use MapsCustomerData;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'customer' => ['required', 'array'],
            ...StoreCustomerRequest::customerRules('customer.'),
            'contacts' => ['nullable', 'array'],
            'contacts.*.contact_name' => ['required', 'string', 'max:255'],
            'contacts.*.designation' => ['nullable', 'string', 'max:150'],
            'contacts.*.department' => ['nullable', 'string', 'max:150'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:50'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.is_active' => ['nullable', 'boolean'],
            'contacts.*.notes' => ['nullable', 'string'],
            'addresses' => ['nullable', 'array'],
            'addresses.*.address_type' => ['required', Rule::enum(CustomerAddressType::class)],
            'addresses.*.address_line_1' => ['required', 'string', 'max:255'],
            'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['nullable', 'string', 'max:120'],
            'addresses.*.state' => ['nullable', 'string', 'max:120'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:40'],
            'addresses.*.country' => ['nullable', 'string', 'max:120'],
            'addresses.*.is_primary' => ['nullable', 'boolean'],
            'addresses.*.is_active' => ['nullable', 'boolean'],
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_name' => ['required', 'string', 'max:255'],
            'bank_accounts.*.account_name' => ['required', 'string', 'max:255'],
            'bank_accounts.*.account_number' => ['required', 'string', 'max:100'],
            'bank_accounts.*.branch_name' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.swift_code' => ['nullable', 'string', 'max:50'],
            'bank_accounts.*.iban' => ['nullable', 'string', 'max:80'],
            'bank_accounts.*.currency_id' => ['nullable', 'integer', 'min:1'],
            'bank_accounts.*.is_primary' => ['nullable', 'boolean'],
            'bank_accounts.*.is_active' => ['nullable', 'boolean'],
            'bank_accounts.*.notes' => ['nullable', 'string'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'min:1'],
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['required', Rule::enum(CustomerDocumentType::class)],
            'documents.*.document_number' => ['nullable', 'string', 'max:150'],
            'documents.*.issued_date' => ['nullable', 'date'],
            'documents.*.expiry_date' => ['nullable', 'date'],
            'documents.*.file_path' => ['nullable', 'string', 'max:500'],
            'documents.*.status' => ['nullable', Rule::enum(CustomerDocumentStatus::class)],
            'documents.*.notes' => ['nullable', 'string'],
            'credit_profile' => ['nullable', 'array'],
            'credit_profile.credit_limit' => ['nullable', 'decimal:0,6', 'gte:0'],
            'credit_profile.credit_period_days' => ['nullable', 'integer', 'min:0'],
            'credit_profile.warning_threshold_percent' => ['nullable', 'decimal:0,6', 'between:0,100'],
            'credit_profile.credit_allowed' => ['nullable', 'boolean'],
            'credit_profile.advance_allowed' => ['nullable', 'boolean'],
            'credit_profile.allow_over_credit' => ['nullable', 'boolean'],
            'credit_profile.allow_partial_payment' => ['nullable', 'boolean'],
            'credit_profile.is_active' => ['nullable', 'boolean'],
            'credit_profile.row_version' => ['prohibited'],
        ];
    }

    public function toData(): CreateCustomerData
    {
        $validated = $this->validated();

        return $this->mapCustomerData((array) $validated['customer'], $validated);
    }
}
