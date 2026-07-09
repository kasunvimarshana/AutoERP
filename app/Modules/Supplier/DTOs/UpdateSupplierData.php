<?php

declare(strict_types=1);

namespace Modules\Supplier\DTOs;

use Modules\Supplier\Enums\SupplierType;

final readonly class UpdateSupplierData
{
    /**
     * Related arrays replace the corresponding reference collection when provided.
     *
     * @param  array<string, mixed>|null  $metadata
     * @param  list<SupplierContactData>|null  $contacts
     * @param  list<SupplierAddressData>|null  $addresses
     * @param  list<SupplierBankAccountData>|null  $bankAccounts
     * @param  list<int>|null  $categoryIds
     * @param  list<SupplierDocumentData>|null  $documents
     * @param  list<SupplierItemMappingData>|null  $itemMappings
     * @param  list<string>  $provided
     */
    public function __construct(
        public int $rowVersion,
        public ?int $organizationUnitId = null,
        public ?string $code = null,
        public ?string $name = null,
        public ?string $legalName = null,
        public ?string $displayName = null,
        public ?SupplierType $supplierType = null,
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
        public ?string $creditLimit = null,
        public ?bool $isCreditAllowed = null,
        public ?bool $isAdvanceAllowed = null,
        public ?string $notes = null,
        public ?array $metadata = null,
        public ?SupplierCreditProfileData $creditProfile = null,
        public ?array $contacts = null,
        public ?array $addresses = null,
        public ?array $bankAccounts = null,
        public ?array $categoryIds = null,
        public ?array $documents = null,
        public ?array $itemMappings = null,
        public array $provided = [],
    ) {}
}
