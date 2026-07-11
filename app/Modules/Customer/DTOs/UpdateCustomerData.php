<?php

declare(strict_types=1);

namespace Modules\Customer\DTOs;

use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Enums\PreferredCommunicationChannel;

final readonly class UpdateCustomerData
{
    /**
     * Related arrays replace the corresponding reference collection when provided.
     *
     * @param  array<string, mixed>|null  $metadata
     * @param  list<CustomerContactData>|null  $contacts
     * @param  list<CustomerAddressData>|null  $addresses
     * @param  list<CustomerBankAccountData>|null  $bankAccounts
     * @param  list<int>|null  $categoryIds
     * @param  list<CustomerDocumentData>|null  $documents
     * @param  list<string>  $provided
     */
    public function __construct(
        public int $rowVersion,
        public ?int $organizationUnitId = null,
        public ?string $code = null,
        public ?string $name = null,
        public ?string $legalName = null,
        public ?string $displayName = null,
        public ?CustomerType $customerType = null,
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
        public ?bool $isTaxExempt = null,
        public ?bool $marketingConsent = null,
        public ?PreferredCommunicationChannel $preferredCommunicationChannel = null,
        public ?string $notes = null,
        public ?array $metadata = null,
        public ?array $contacts = null,
        public ?array $addresses = null,
        public ?array $bankAccounts = null,
        public ?array $categoryIds = null,
        public ?array $documents = null,
        public array $provided = [],
    ) {}
}
