<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Supplier\Domain\Constants\SupplierStatus;

final class UpsertSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'supplier_code' => array_merge($required, ['string', 'max:60']),
            'supplier_name' => array_merge($required, ['string', 'max:180']),
            'legal_name' => ['nullable', 'string', 'max:180'],
            'display_name' => ['nullable', 'string', 'max:180'],
            'supplier_type' => ['nullable', 'string', 'max:60'],
            'category_id' => ['nullable', 'integer', 'min:1', 'exists:supplier_categories,id'],
            'registration_number' => ['nullable', 'string', 'max:120'],
            'tax_number' => ['nullable', 'string', 'max:120'],
            'vat_number' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'default_currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'default_payment_term_id' => ['nullable', 'integer', 'min:1', 'exists:payment_terms,id'],
            'default_payable_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'default_expense_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:' . implode(',', SupplierStatus::values())],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'status_reason' => ['nullable', 'string', 'max:255'],

            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['required_with:contacts', 'string', 'max:180'],
            'contacts.*.designation' => ['nullable', 'string', 'max:180'],
            'contacts.*.department' => ['nullable', 'string', 'max:180'],
            'contacts.*.email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:100'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:100'],
            'contacts.*.whatsapp' => ['nullable', 'string', 'max:100'],
            'contacts.*.is_billing_contact' => ['nullable', 'boolean'],
            'contacts.*.is_procurement_contact' => ['nullable', 'boolean'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.is_active' => ['nullable', 'boolean'],
            'contacts.*.metadata' => ['nullable', 'array'],

            'addresses' => ['nullable', 'array'],
            'addresses.*.type' => ['nullable', 'string', 'max:50'],
            'addresses.*.label' => ['nullable', 'string', 'max:120'],
            'addresses.*.contact_person' => ['nullable', 'string', 'max:180'],
            'addresses.*.contact_phone' => ['nullable', 'string', 'max:100'],
            'addresses.*.address_line1' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.address_line2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:120'],
            'addresses.*.state' => ['nullable', 'string', 'max:120'],
            'addresses.*.postal_code' => ['required_with:addresses', 'string', 'max:60'],
            'addresses.*.country_id' => ['nullable', 'integer', 'min:1', 'exists:countries,id'],
            'addresses.*.is_default' => ['nullable', 'boolean'],
            'addresses.*.is_default_billing' => ['nullable', 'boolean'],
            'addresses.*.is_default_shipping' => ['nullable', 'boolean'],
            'addresses.*.is_active' => ['nullable', 'boolean'],
            'addresses.*.geo_lat' => ['nullable', 'numeric'],
            'addresses.*.geo_lng' => ['nullable', 'numeric'],
            'addresses.*.metadata' => ['nullable', 'array'],

            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.account_name' => ['required_with:bank_accounts', 'string', 'max:180'],
            'bank_accounts.*.account_number' => ['required_with:bank_accounts', 'string', 'max:120'],
            'bank_accounts.*.iban' => ['nullable', 'string', 'max:120'],
            'bank_accounts.*.swift_code' => ['nullable', 'string', 'max:50'],
            'bank_accounts.*.bank_name' => ['required_with:bank_accounts', 'string', 'max:180'],
            'bank_accounts.*.branch_name' => ['nullable', 'string', 'max:180'],
            'bank_accounts.*.bank_code' => ['nullable', 'string', 'max:60'],
            'bank_accounts.*.branch_code' => ['nullable', 'string', 'max:60'],
            'bank_accounts.*.currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'bank_accounts.*.is_primary' => ['nullable', 'boolean'],
            'bank_accounts.*.is_active' => ['nullable', 'boolean'],
            'bank_accounts.*.metadata' => ['nullable', 'array'],

            'tax_profile' => ['nullable', 'array'],
            'tax_profile.tax_identifier' => ['nullable', 'string', 'max:120'],
            'tax_profile.vat_identifier' => ['nullable', 'string', 'max:120'],
            'tax_profile.tax_type' => ['nullable', 'string', 'max:80'],
            'tax_profile.withholding_rate' => ['nullable', 'numeric', 'min:0'],
            'tax_profile.is_tax_exempt' => ['nullable', 'boolean'],
            'tax_profile.tax_exempt_until' => ['nullable', 'date'],
            'tax_profile.is_active' => ['nullable', 'boolean'],
            'tax_profile.metadata' => ['nullable', 'array'],

            'create_user_access' => ['nullable', 'boolean'],
            'link_user_id' => ['nullable', 'integer', 'min:1'],
            'user_access' => ['nullable', 'array'],
            'user_access.access_type' => ['nullable', 'string', 'max:60'],
            'user_access.is_primary' => ['nullable', 'boolean'],
            'user_access.metadata' => ['nullable', 'array'],
            'user_access.user' => ['nullable', 'array'],
            'user_access.user.name' => ['nullable', 'string', 'max:255'],
            'user_access.user.email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'user_access.user.password' => ['nullable', 'string', 'min:8', 'max:255'],
        ];
    }
}
