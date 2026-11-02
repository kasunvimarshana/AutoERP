<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests\Concerns;

use Modules\Customer\DTOs\CreateCustomerData;
use Modules\Customer\DTOs\CustomerAddressData;
use Modules\Customer\DTOs\CustomerBankAccountData;
use Modules\Customer\DTOs\CustomerContactData;
use Modules\Customer\DTOs\CustomerCreditProfileData;
use Modules\Customer\DTOs\CustomerDocumentData;
use Modules\Customer\Enums\CustomerAddressType;
use Modules\Customer\Enums\CustomerDocumentStatus;
use Modules\Customer\Enums\CustomerDocumentType;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Enums\PreferredCommunicationChannel;

trait MapsCustomerData
{
    private function mapCustomerData(array $customer, array $relations = []): CreateCustomerData
    {
        return new CreateCustomerData(
            tenantId: $this->tenantId(),
            code: (string) $customer['code'],
            name: (string) $customer['name'],
            customerType: CustomerType::from((string) $customer['customer_type']),
            organizationUnitId: $this->organizationUnitId(),
            customerNumber: $this->nullableString($customer, 'customer_number'),
            legalName: $this->nullableString($customer, 'legal_name'),
            displayName: $this->nullableString($customer, 'display_name'),
            status: CustomerStatus::from((string) ($customer['status'] ?? CustomerStatus::PendingApproval->value)),
            email: $this->nullableString($customer, 'email'),
            phone: $this->nullableString($customer, 'phone'),
            mobile: $this->nullableString($customer, 'mobile'),
            website: $this->nullableString($customer, 'website'),
            defaultCurrencyId: $this->nullableInt($customer, 'default_currency_id'),
            paymentTermId: $this->nullableInt($customer, 'payment_term_id'),
            taxRegistrationNumber: $this->nullableString($customer, 'tax_registration_number'),
            vatNumber: $this->nullableString($customer, 'vat_number'),
            svatNumber: $this->nullableString($customer, 'svat_number'),
            businessRegistrationNumber: $this->nullableString($customer, 'business_registration_number'),
            creditLimit: (string) ($customer['credit_limit'] ?? '0.000000'),
            openingBalance: (string) ($customer['opening_balance'] ?? '0.000000'),
            isCreditAllowed: (bool) ($customer['is_credit_allowed'] ?? true),
            isAdvanceAllowed: (bool) ($customer['is_advance_allowed'] ?? true),
            isTaxExempt: (bool) ($customer['is_tax_exempt'] ?? false),
            marketingConsent: (bool) ($customer['marketing_consent'] ?? false),
            preferredCommunicationChannel: isset($customer['preferred_communication_channel']) && $customer['preferred_communication_channel'] !== null
                ? PreferredCommunicationChannel::from((string) $customer['preferred_communication_channel'])
                : null,
            notes: $this->nullableString($customer, 'notes'),
            metadata: $customer['metadata'] ?? null,
            createdBy: $this->currentUserId(),
            creditProfile: isset($relations['credit_profile']) && is_array($relations['credit_profile'])
                ? $this->mapCreditProfile($relations['credit_profile'])
                : null,
            contacts: array_map(fn (array $row): CustomerContactData => $this->mapContact($row), $relations['contacts'] ?? []),
            addresses: array_map(fn (array $row): CustomerAddressData => $this->mapAddress($row), $relations['addresses'] ?? []),
            bankAccounts: array_map(fn (array $row): CustomerBankAccountData => $this->mapBankAccount($row), $relations['bank_accounts'] ?? []),
            categoryIds: array_values(array_map('intval', $relations['categories'] ?? [])),
            documents: array_map(fn (array $row): CustomerDocumentData => $this->mapDocument($row), $relations['documents'] ?? []),
        );
    }

    private function mapContact(array $row): CustomerContactData
    {
        return new CustomerContactData(
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

    private function mapAddress(array $row): CustomerAddressData
    {
        return new CustomerAddressData(
            addressType: CustomerAddressType::from((string) $row['address_type']),
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

    private function mapBankAccount(array $row): CustomerBankAccountData
    {
        return new CustomerBankAccountData(
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

    private function mapDocument(array $row): CustomerDocumentData
    {
        return new CustomerDocumentData(
            documentType: CustomerDocumentType::from((string) $row['document_type']),
            documentNumber: $row['document_number'] ?? null,
            issuedDate: $row['issued_date'] ?? null,
            expiryDate: $row['expiry_date'] ?? null,
            filePath: $row['file_path'] ?? null,
            status: CustomerDocumentStatus::from((string) ($row['status'] ?? CustomerDocumentStatus::Pending->value)),
            notes: $row['notes'] ?? null,
        );
    }

    private function mapCreditProfile(array $row): CustomerCreditProfileData
    {
        return new CustomerCreditProfileData(
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
