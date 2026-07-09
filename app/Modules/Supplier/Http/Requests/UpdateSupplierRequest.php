<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\DTOs\UpdateSupplierData;
use Modules\Supplier\Enums\SupplierType;

final class UpdateSupplierRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'row_version' => ['required', 'integer', 'min:1'],
            'code' => ['sometimes', 'string', 'max:80'],
            'name' => ['sometimes', 'string', 'max:255'],
            'supplier_type' => ['sometimes', Rule::enum(SupplierType::class)],
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
            'opening_balance' => ['prohibited'],
            'is_credit_allowed' => ['nullable', 'boolean'],
            'is_advance_allowed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toData(): UpdateSupplierData
    {
        return new UpdateSupplierData(
            rowVersion: (int) $this->input('row_version'),
            organizationUnitId: $this->organizationUnitId(),
            code: $this->stringOrNull('code'),
            name: $this->stringOrNull('name'),
            legalName: $this->stringOrNull('legal_name'),
            displayName: $this->stringOrNull('display_name'),
            supplierType: $this->filled('supplier_type') ? SupplierType::from((string) $this->input('supplier_type')) : null,
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
            isCreditAllowed: $this->has('is_credit_allowed') ? $this->boolean('is_credit_allowed') : null,
            isAdvanceAllowed: $this->has('is_advance_allowed') ? $this->boolean('is_advance_allowed') : null,
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
