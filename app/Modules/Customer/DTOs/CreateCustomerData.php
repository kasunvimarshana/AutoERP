<?php

declare(strict_types=1);

namespace Modules\Customer\DTOs;

use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Enums\PreferredCommunicationChannel;

final readonly class CreateCustomerData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  list<CustomerContactData>  $contacts
     * @param  list<CustomerAddressData>  $addresses
     * @param  list<CustomerBankAccountData>  $bankAccounts
     * @param  list<int>  $categoryIds
     * @param  list<CustomerDocumentData>  $documents
     */
    public function __construct(
        public int $tenantId,
        public string $code,
        public string $name,
        public CustomerType $customerType,
        public ?int $organizationUnitId = null,
        public ?string $customerNumber = null,
        public ?string $legalName = null,
        public ?string $displayName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $mobile = null,
        public ?string $website = null,
        public ?int $defaultCurrencyId = null,
        public ?int $paymentTermId = null,
        public ?string $taxRegistrationNumber = null,
        public ?string $vatNumber = null,
        public ?string $svatNumber = null,
        public ?string $businessRegistrationNumber = null,
        public string $creditLimit = '0.000000',
        public bool $isCreditAllowed = true,
        public bool $isAdvanceAllowed = true,
        public bool $isTaxExempt = false,
        public bool $marketingConsent = false,
        public ?PreferredCommunicationChannel $preferredCommunicationChannel = null,
        public ?string $notes = null,
        public ?array $metadata = null,
        public ?int $createdBy = null,
        public ?CustomerCreditProfileData $creditProfile = null,
        public array $contacts = [],
        public array $addresses = [],
        public array $bankAccounts = [],
        public array $categoryIds = [],
        public array $documents = [],
    ) {}
}
