<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests\Concerns;

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

trait MapsSupplierData
{
    private function mapSupplierData(array $supplier, array $relations = []): CreateSupplierData
    {
        return new CreateSupplierData(
            tenantId: $this->tenantId(),
            code: (string) $supplier['code'],
            name: (string) $supplier['name'],
            supplierType: SupplierType::from((string) $supplier['supplier_type']),
            organizationUnitId: $this->organizationUnitId(),
            supplierNumber: $this->nullableString($supplier, 'supplier_number'),
            legalName: $this->nullableString($supplier, 'legal_name'),
            displayName: $this->nullableString($supplier, 'display_name'),
            status: SupplierStatus::from((string) ($supplier['status'] ?? SupplierStatus::PendingApproval->value)),
            email: $this->nullableString($supplier, 'email'),
            phone: $this->nullableString($supplier, 'phone'),
            mobile: $this->nullableString($supplier, 'mobile'),
            website: $this->nullableString($supplier, 'website'),
            defaultCurrencyId: $this->nullableInt($supplier, 'default_currency_id'),
            paymentTermId: $this->nullableInt($supplier, 'payment_term_id'),
            taxRegistrationNumber: $this->nullableString($supplier, 'tax_registration_number'),
            vatNumber: $this->nullableString($supplier, 'vat_number'),
            svatNumber: $this->nullableString($supplier, 'svat_number'),
            businessRegistrationNumber: $this->nullableString($supplier, 'business_registration_number'),
            creditLimit: (string) ($supplier['credit_limit'] ?? '0.000000'),
            isCreditAllowed: (bool) ($supplier['is_credit_allowed'] ?? true),
            isAdvanceAllowed: (bool) ($supplier['is_advance_allowed'] ?? true),
            notes: $this->nullableString($supplier, 'notes'),
            metadata: $supplier['metadata'] ?? null,
            createdBy: $this->currentUserId(),
            creditProfile: isset($relations['credit_profile']) && is_array($relations['credit_profile'])
                ? $this->mapCreditProfile($relations['credit_profile'])
                : null,
            contacts: array_map(fn (array $row): SupplierContactData => $this->mapContact($row), $relations['contacts'] ?? []),
            addresses: array_map(fn (array $row): SupplierAddressData => $this->mapAddress($row), $relations['addresses'] ?? []),
            bankAccounts: array_map(fn (array $row): SupplierBankAccountData => $this->mapBankAccount($row), $relations['bank_accounts'] ?? []),
            categoryIds: array_values(array_map('intval', $relations['categories'] ?? [])),
            documents: array_map(fn (array $row): SupplierDocumentData => $this->mapDocument($row), $relations['documents'] ?? []),
            itemMappings: array_map(fn (array $row): SupplierItemMappingData => $this->mapItemMapping($row), $relations['item_mappings'] ?? []),
        );
    }

    private function mapContact(array $row): SupplierContactData
    {
        return new SupplierContactData(
            contactName: (string) $row['contact_name'],
            designation: $row['designation'] ?? null,
            department: $row['department'] ?? null,
            email: $row['email'] ?? null,
            phone: $row['phone'] ?? null,
            mobile: $row['mobile'] ?? null,
            isPrimary: (bool) ($row['is_primary'] ?? false),
            isActive: (bool) ($row['is_active'] ?? true),
            notes: $row['notes'] ?? null,
        );
    }

    private function mapAddress(array $row): SupplierAddressData
    {
        return new SupplierAddressData(
            addressType: SupplierAddressType::from((string) $row['address_type']),
            addressLine1: (string) $row['address_line_1'],
            addressLine2: $row['address_line_2'] ?? null,
            city: $row['city'] ?? null,
            state: $row['state'] ?? null,
            postalCode: $row['postal_code'] ?? null,
            country: $row['country'] ?? null,
            isPrimary: (bool) ($row['is_primary'] ?? false),
            isActive: (bool) ($row['is_active'] ?? true),
        );
    }

    private function mapBankAccount(array $row): SupplierBankAccountData
    {
        return new SupplierBankAccountData(
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
        );
    }

    private function mapDocument(array $row): SupplierDocumentData
    {
        return new SupplierDocumentData(
            documentType: SupplierDocumentType::from((string) $row['document_type']),
            documentNumber: $row['document_number'] ?? null,
            issuedDate: $row['issued_date'] ?? null,
            expiryDate: $row['expiry_date'] ?? null,
            filePath: $row['file_path'] ?? null,
            status: SupplierDocumentStatus::from((string) ($row['status'] ?? SupplierDocumentStatus::Pending->value)),
            notes: $row['notes'] ?? null,
        );
    }

    private function mapItemMapping(array $row): SupplierItemMappingData
    {
        return new SupplierItemMappingData(
            itemId: (int) $row['item_id'],
            itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
            supplierItemCode: $row['supplier_item_code'] ?? null,
            supplierItemName: $row['supplier_item_name'] ?? null,
            defaultPurchaseUomId: isset($row['default_purchase_uom_id']) ? (int) $row['default_purchase_uom_id'] : null,
            minimumOrderQuantity: (string) ($row['minimum_order_quantity'] ?? '0.000000'),
            leadTimeDays: isset($row['lead_time_days']) ? (int) $row['lead_time_days'] : null,
            isPreferred: (bool) ($row['is_preferred'] ?? false),
            isActive: (bool) ($row['is_active'] ?? true),
        );
    }

    private function mapCreditProfile(array $row): SupplierCreditProfileData
    {
        return new SupplierCreditProfileData(
            creditLimit: (string) ($row['credit_limit'] ?? '0.000000'),
            creditPeriodDays: isset($row['credit_period_days']) ? (int) $row['credit_period_days'] : null,
            warningThresholdPercent: (string) ($row['warning_threshold_percent'] ?? '80.000000'),
            allowOverCredit: (bool) ($row['allow_over_credit'] ?? false),
            allowPartialPayment: (bool) ($row['allow_partial_payment'] ?? true),
            isActive: (bool) ($row['is_active'] ?? true),
        );
    }

    private function nullableInt(array $data, string $key): ?int
    {
        return isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;
    }

    private function nullableString(array $data, string $key): ?string
    {
        return isset($data[$key]) && trim((string) $data[$key]) !== '' ? (string) $data[$key] : null;
    }
}
