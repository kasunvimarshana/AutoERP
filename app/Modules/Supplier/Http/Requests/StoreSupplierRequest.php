<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\DTOs\CreateSupplierData;
use Modules\Supplier\DTOs\SupplierAddressData;
use Modules\Supplier\DTOs\SupplierBankAccountData;
use Modules\Supplier\DTOs\SupplierContactData;
use Modules\Supplier\DTOs\SupplierCreditProfileData;
use Modules\Supplier\DTOs\SupplierDocumentData;
use Modules\Supplier\DTOs\SupplierItemMappingData;
use Modules\Supplier\Enums\SupplierAddressType;
use Modules\Supplier\Enums\SupplierDocumentStatus;
use Modules\Supplier\Enums\SupplierDocumentType;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Enums\SupplierType;

final class StoreSupplierRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'supplier_number' => ['nullable', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'supplier_type' => ['required', Rule::enum(SupplierType::class)],
            'status' => ['nullable', Rule::enum(SupplierStatus::class)],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'default_currency_id' => ['nullable', 'integer', 'min:1'],
            'payment_term_id' => ['nullable', 'integer', 'min:1'],
            'tax_registration_number' => ['nullable', 'string', 'max:100'],
            'vat_number' => ['nullable', 'string', 'max:100'],
            'svat_number' => ['nullable', 'string', 'max:100'],
            'business_registration_number' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'decimal:0,6', 'min:0'],
            'opening_balance' => ['nullable', 'decimal:0,6', 'min:0'],
            'is_credit_allowed' => ['nullable', 'boolean'],
            'is_advance_allowed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.contact_name' => ['required', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'addresses' => ['nullable', 'array'],
            'addresses.*.address_type' => ['required', Rule::enum(SupplierAddressType::class)],
            'addresses.*.address_line_1' => ['required', 'string', 'max:255'],
            'addresses.*.is_primary' => ['nullable', 'boolean'],
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_name' => ['required', 'string', 'max:255'],
            'bank_accounts.*.account_name' => ['required', 'string', 'max:255'],
            'bank_accounts.*.account_number' => ['required', 'string', 'max:100'],
            'bank_accounts.*.is_primary' => ['nullable', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'min:1'],
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['required', Rule::enum(SupplierDocumentType::class)],
            'documents.*.status' => ['nullable', Rule::enum(SupplierDocumentStatus::class)],
            'item_mappings' => ['nullable', 'array'],
            'item_mappings.*.item_id' => ['required', 'integer', 'min:1'],
            'item_mappings.*.minimum_order_quantity' => ['nullable', 'decimal:0,6', 'min:0'],
            'credit_profile' => ['nullable', 'array'],
            'credit_profile.credit_limit' => ['nullable', 'decimal:0,6', 'min:0'],
            'credit_profile.warning_threshold_percent' => ['nullable', 'decimal:0,6', 'between:0,100'],
        ];
    }

    public function toData(): CreateSupplierData
    {
        return new CreateSupplierData(
            tenantId: $this->tenantId(),
            code: $this->string('code')->toString(),
            name: $this->string('name')->toString(),
            supplierType: SupplierType::from($this->string('supplier_type')->toString()),
            organizationUnitId: $this->organizationUnitId(),
            supplierNumber: $this->nullableString('supplier_number'),
            legalName: $this->nullableString('legal_name'),
            displayName: $this->nullableString('display_name'),
            status: SupplierStatus::from((string) $this->input('status', SupplierStatus::PendingApproval->value)),
            email: $this->nullableString('email'),
            phone: $this->nullableString('phone'),
            mobile: $this->nullableString('mobile'),
            website: $this->nullableString('website'),
            defaultCurrencyId: $this->integerOrNull('default_currency_id'),
            paymentTermId: $this->integerOrNull('payment_term_id'),
            taxRegistrationNumber: $this->nullableString('tax_registration_number'),
            vatNumber: $this->nullableString('vat_number'),
            svatNumber: $this->nullableString('svat_number'),
            businessRegistrationNumber: $this->nullableString('business_registration_number'),
            creditLimit: (string) $this->input('credit_limit', '0.000000'),
            openingBalance: (string) $this->input('opening_balance', '0.000000'),
            isCreditAllowed: $this->boolean('is_credit_allowed', true),
            isAdvanceAllowed: $this->boolean('is_advance_allowed', true),
            notes: $this->nullableString('notes'),
            metadata: $this->input('metadata'),
            createdBy: $this->currentUserId(),
            creditProfile: $this->creditProfile(),
            contacts: $this->contacts(),
            addresses: $this->addresses(),
            bankAccounts: $this->bankAccounts(),
            categoryIds: array_map('intval', $this->input('category_ids', [])),
            documents: $this->documents(),
            itemMappings: $this->itemMappings(),
        );
    }

    private function contacts(): array
    {
        return array_map(static fn (array $row): SupplierContactData => new SupplierContactData(
            contactName: (string) $row['contact_name'],
            designation: $row['designation'] ?? null,
            department: $row['department'] ?? null,
            email: $row['email'] ?? null,
            phone: $row['phone'] ?? null,
            mobile: $row['mobile'] ?? null,
            isPrimary: (bool) ($row['is_primary'] ?? false),
            isActive: (bool) ($row['is_active'] ?? true),
            notes: $row['notes'] ?? null,
        ), $this->input('contacts', []));
    }

    private function addresses(): array
    {
        return array_map(static fn (array $row): SupplierAddressData => new SupplierAddressData(
            addressType: SupplierAddressType::from((string) $row['address_type']),
            addressLine1: (string) $row['address_line_1'],
            addressLine2: $row['address_line_2'] ?? null,
            city: $row['city'] ?? null,
            state: $row['state'] ?? null,
            postalCode: $row['postal_code'] ?? null,
            country: $row['country'] ?? null,
            isPrimary: (bool) ($row['is_primary'] ?? false),
            isActive: (bool) ($row['is_active'] ?? true),
        ), $this->input('addresses', []));
    }

    private function bankAccounts(): array
    {
        return array_map(static fn (array $row): SupplierBankAccountData => new SupplierBankAccountData(
            bankName: (string) $row['bank_name'],
            accountName: (string) $row['account_name'],
            accountNumber: (string) $row['account_number'],
            branchName: $row['branch_name'] ?? null,
            swiftCode: $row['swift_code'] ?? null,
            iban: $row['iban'] ?? null,
            currencyId: isset($row['currency_id']) ? (int) $row['currency_id'] : null,
            isPrimary: (bool) ($row['is_primary'] ?? false),
            isActive: (bool) ($row['is_active'] ?? true),
            notes: $row['notes'] ?? null,
        ), $this->input('bank_accounts', []));
    }

    private function creditProfile(): ?SupplierCreditProfileData
    {
        $row = $this->input('credit_profile');
        if (! is_array($row)) {
            return null;
        }

        return new SupplierCreditProfileData(
            creditLimit: (string) ($row['credit_limit'] ?? '0.000000'),
            creditPeriodDays: isset($row['credit_period_days']) ? (int) $row['credit_period_days'] : null,
            warningThresholdPercent: (string) ($row['warning_threshold_percent'] ?? '80.000000'),
            allowOverCredit: (bool) ($row['allow_over_credit'] ?? false),
            allowPartialPayment: (bool) ($row['allow_partial_payment'] ?? true),
            isActive: (bool) ($row['is_active'] ?? true),
        );
    }

    private function documents(): array
    {
        return array_map(static fn (array $row): SupplierDocumentData => new SupplierDocumentData(
            documentType: SupplierDocumentType::from((string) $row['document_type']),
            documentNumber: $row['document_number'] ?? null,
            issuedDate: $row['issued_date'] ?? null,
            expiryDate: $row['expiry_date'] ?? null,
            filePath: $row['file_path'] ?? null,
            status: SupplierDocumentStatus::from((string) ($row['status'] ?? SupplierDocumentStatus::Pending->value)),
            notes: $row['notes'] ?? null,
        ), $this->input('documents', []));
    }

    private function itemMappings(): array
    {
        return array_map(static fn (array $row): SupplierItemMappingData => new SupplierItemMappingData(
            itemId: (int) $row['item_id'],
            itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
            supplierItemCode: $row['supplier_item_code'] ?? null,
            supplierItemName: $row['supplier_item_name'] ?? null,
            defaultPurchaseUomId: isset($row['default_purchase_uom_id']) ? (int) $row['default_purchase_uom_id'] : null,
            minimumOrderQuantity: (string) ($row['minimum_order_quantity'] ?? '0.000000'),
            leadTimeDays: isset($row['lead_time_days']) ? (int) $row['lead_time_days'] : null,
            isPreferred: (bool) ($row['is_preferred'] ?? false),
            isActive: (bool) ($row['is_active'] ?? true),
        ), $this->input('item_mappings', []));
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->input($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function integerOrNull(string $key): ?int
    {
        $value = $this->input($key);

        return is_numeric($value) ? (int) $value : null;
    }
}
