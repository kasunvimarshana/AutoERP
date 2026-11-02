<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Customer\DTOs\UpdateCustomerData;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Enums\PreferredCommunicationChannel;

final class UpdateCustomerRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['sometimes', 'string', 'max:80'],
            'name' => ['sometimes', 'string', 'max:255'],
            'customer_type' => ['sometimes', Rule::enum(CustomerType::class)],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+().\s-]{5,50}$/'],
            'mobile' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+().\s-]{5,50}$/'],
            'website' => ['nullable', 'url', 'max:255'],
            'default_currency_id' => ['nullable', 'integer', 'min:1'],
            'payment_term_id' => ['nullable', 'integer', 'min:1'],
            'tax_registration_number' => ['nullable', 'string', 'max:100'],
            'vat_number' => ['nullable', 'string', 'max:100'],
            'svat_number' => ['nullable', 'string', 'max:100'],
            'business_registration_number' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'decimal:0,6', 'min:0'],
            'opening_balance' => ['nullable', 'decimal:0,6'],
            'is_credit_allowed' => ['nullable', 'boolean'],
            'is_advance_allowed' => ['nullable', 'boolean'],
            'is_tax_exempt' => ['nullable', 'boolean'],
            'marketing_consent' => ['nullable', 'boolean'],
            'preferred_communication_channel' => ['nullable', Rule::enum(PreferredCommunicationChannel::class)],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toData(): UpdateCustomerData
    {
        return new UpdateCustomerData(
            organizationUnitId: $this->organizationUnitId(),
            code: $this->stringOrNull('code'),
            name: $this->stringOrNull('name'),
            legalName: $this->stringOrNull('legal_name'),
            displayName: $this->stringOrNull('display_name'),
            customerType: $this->filled('customer_type') ? CustomerType::from((string) $this->input('customer_type')) : null,
            email: $this->stringOrNull('email'),
            phone: $this->stringOrNull('phone'),
            mobile: $this->stringOrNull('mobile'),
            website: $this->stringOrNull('website'),
            defaultCurrencyId: $this->integerOrNull('default_currency_id'),
            paymentTermId: $this->integerOrNull('payment_term_id'),
            taxRegistrationNumber: $this->stringOrNull('tax_registration_number'),
            vatNumber: $this->stringOrNull('vat_number'),
            svatNumber: $this->stringOrNull('svat_number'),
            businessRegistrationNumber: $this->stringOrNull('business_registration_number'),
            creditLimit: $this->filled('credit_limit') ? (string) $this->input('credit_limit') : null,
            openingBalance: $this->filled('opening_balance') ? (string) $this->input('opening_balance') : null,
            isCreditAllowed: $this->has('is_credit_allowed') ? $this->boolean('is_credit_allowed') : null,
            isAdvanceAllowed: $this->has('is_advance_allowed') ? $this->boolean('is_advance_allowed') : null,
            isTaxExempt: $this->has('is_tax_exempt') ? $this->boolean('is_tax_exempt') : null,
            marketingConsent: $this->has('marketing_consent') ? $this->boolean('marketing_consent') : null,
            preferredCommunicationChannel: $this->filled('preferred_communication_channel')
                ? PreferredCommunicationChannel::from((string) $this->input('preferred_communication_channel'))
                : null,
            notes: $this->stringOrNull('notes'),
            metadata: $this->has('metadata') ? $this->input('metadata') : null,
            provided: array_keys($this->all()),
        );
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }

    private function integerOrNull(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }
}
