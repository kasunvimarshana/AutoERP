<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Customer\Domain\Constants\CustomerStatus;

final class UpsertCustomerRequest extends FormRequest
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
            'customer_code' => array_merge($required, ['string', 'max:60']),
            'customer_name' => array_merge($required, ['string', 'max:180']),
            'legal_name' => ['nullable', 'string', 'max:180'],
            'display_name' => ['nullable', 'string', 'max:180'],
            'customer_type' => ['nullable', 'string', 'max:60'],
            'category_id' => ['nullable', 'integer', 'min:1', 'exists:customer_categories,id'],
            'registration_number' => ['nullable', 'string', 'max:120'],
            'tax_number' => ['nullable', 'string', 'max:120'],
            'vat_number' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'default_currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'default_payment_term_id' => ['nullable', 'integer', 'min:1', 'exists:payment_terms,id'],
            'default_receivable_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'default_income_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'credit_hold' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:' . implode(',', CustomerStatus::values())],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'status_reason' => ['nullable', 'string', 'max:255'],

            'contacts' => ['nullable', 'array'],
            'contacts.*.contact_name' => ['required_with:contacts', 'string', 'max:180'],
            'contacts.*.designation' => ['nullable', 'string', 'max:180'],
            'contacts.*.department' => ['nullable', 'string', 'max:180'],
            'contacts.*.email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:100'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:100'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.is_active' => ['nullable', 'boolean'],
            'contacts.*.notes' => ['nullable', 'string'],
            'contacts.*.metadata' => ['nullable', 'array'],

            'addresses' => ['nullable', 'array'],
            'addresses.*.address_type' => ['nullable', 'string', 'max:60'],
            'addresses.*.label' => ['nullable', 'string', 'max:120'],
            'addresses.*.contact_person' => ['nullable', 'string', 'max:180'],
            'addresses.*.contact_phone' => ['nullable', 'string', 'max:100'],
            'addresses.*.address_line_1' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:120'],
            'addresses.*.state_province' => ['nullable', 'string', 'max:120'],
            'addresses.*.postal_code' => ['required_with:addresses', 'string', 'max:60'],
            'addresses.*.country_id' => ['nullable', 'integer', 'min:1', 'exists:countries,id'],
            'addresses.*.country_name' => ['nullable', 'string', 'max:120'],
            'addresses.*.is_primary' => ['nullable', 'boolean'],
            'addresses.*.is_primary_billing' => ['nullable', 'boolean'],
            'addresses.*.is_primary_shipping' => ['nullable', 'boolean'],
            'addresses.*.is_active' => ['nullable', 'boolean'],
            'addresses.*.geo_lat' => ['nullable', 'numeric'],
            'addresses.*.geo_lng' => ['nullable', 'numeric'],
            'addresses.*.metadata' => ['nullable', 'array'],

            'tax_profile' => ['nullable', 'array'],
            'tax_profile.tax_registration_number' => ['nullable', 'string', 'max:120'],
            'tax_profile.vat_number' => ['nullable', 'string', 'max:120'],
            'tax_profile.tax_group_id' => ['nullable', 'integer', 'min:1'],
            'tax_profile.tax_exempt' => ['nullable', 'boolean'],
            'tax_profile.exemption_certificate_reference' => ['nullable', 'string', 'max:120'],
            'tax_profile.valid_from' => ['nullable', 'date'],
            'tax_profile.valid_to' => ['nullable', 'date'],
            'tax_profile.is_active' => ['nullable', 'boolean'],
            'tax_profile.metadata' => ['nullable', 'array'],

            'credit_profile' => ['nullable', 'array'],
            'credit_profile.credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_profile.credit_days' => ['nullable', 'integer', 'min:0'],
            'credit_profile.credit_hold' => ['nullable', 'boolean'],
            'credit_profile.credit_hold_reason' => ['nullable', 'string', 'max:255'],
            'credit_profile.allow_credit_override' => ['nullable', 'boolean'],
            'credit_profile.is_active' => ['nullable', 'boolean'],
            'credit_profile.metadata' => ['nullable', 'array'],

            'create_user' => ['nullable', 'boolean'],
            'link_user_id' => ['nullable', 'integer', 'min:1'],
            'user_access' => ['nullable', 'array'],
            'user_access.access_role' => ['nullable', 'string', 'max:60'],
            'user_access.is_primary' => ['nullable', 'boolean'],
            'user_access.invited' => ['nullable', 'boolean'],
            'user_access.metadata' => ['nullable', 'array'],
            'user_access.user' => ['nullable', 'array'],
            'user_access.user.name' => ['nullable', 'string', 'max:255'],
            'user_access.user.email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'user_access.user.password' => ['nullable', 'string', 'min:8', 'max:255'],
        ];
    }
}