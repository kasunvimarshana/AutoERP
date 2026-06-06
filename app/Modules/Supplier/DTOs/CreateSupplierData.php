<?php

declare(strict_types=1);

namespace Modules\Supplier\DTOs;

use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Enums\SupplierType;

final readonly class CreateSupplierData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  list<SupplierContactData>  $contacts
     * @param  list<SupplierAddressData>  $addresses
     * @param  list<SupplierBankAccountData>  $bankAccounts
     * @param  list<int>  $categoryIds
     * @param  list<SupplierDocumentData>  $documents
     * @param  list<SupplierItemMappingData>  $itemMappings
     */
    public function __construct(
        public int $tenantId,
        public string $code,
        public string $name,
        public SupplierType $supplierType,
        public ?int $organizationUnitId = null,
        public ?string $supplierNumber = null,
        public ?string $legalName = null,
        public ?string $displayName = null,
        public SupplierStatus $status = SupplierStatus::PendingApproval,
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
        public string $openingBalance = '0.000000',
        public bool $isCreditAllowed = true,
        public bool $isAdvanceAllowed = true,
        public ?string $notes = null,
        public ?array $metadata = null,
        public ?int $createdBy = null,
        public ?SupplierCreditProfileData $creditProfile = null,
        public array $contacts = [],
        public array $addresses = [],
        public array $bankAccounts = [],
        public array $categoryIds = [],
        public array $documents = [],
        public array $itemMappings = [],
    ) {}
}
