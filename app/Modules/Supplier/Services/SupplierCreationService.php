<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Supplier\DTOs\CreateSupplierData;
use Modules\Supplier\DTOs\SupplierCreditProfileData;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Validators\SupplierValidationService;

final class SupplierCreationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SupplierValidationService $validator,
        private readonly SupplierNumberService $numbers,
        private readonly SupplierContactService $contacts,
        private readonly SupplierAddressService $addresses,
        private readonly SupplierBankAccountService $bankAccounts,
        private readonly SupplierCategoryService $categories,
        private readonly SupplierDocumentService $documents,
        private readonly SupplierItemMappingService $itemMappings,
        private readonly SupplierCreditProfileService $creditProfiles,
        private readonly SupplierStatusService $statuses,
    ) {}

    public function create(CreateSupplierData $data): Supplier
    {
        $this->validator->validateCreate($data);

        return DB::transaction(function () use ($data): Supplier {
            $supplier = Supplier::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'supplier_number' => $data->supplierNumber ?? $this->numbers->next($data->tenantId),
                'code' => $data->code,
                'name' => $data->name,
                'legal_name' => $data->legalName,
                'display_name' => $data->displayName,
                'supplier_type' => $data->supplierType,
                'status' => $data->status,
                'email' => $data->email,
                'phone' => $data->phone,
                'mobile' => $data->mobile,
                'website' => $data->website,
                'default_currency_id' => $data->defaultCurrencyId,
                'payment_term_id' => $data->paymentTermId,
                'tax_registration_number' => $data->taxRegistrationNumber,
                'vat_number' => $data->vatNumber,
                'svat_number' => $data->svatNumber,
                'business_registration_number' => $data->businessRegistrationNumber,
                'credit_limit' => $this->math->normalize($data->creditProfile?->creditLimit ?? $data->creditLimit),
                'opening_balance' => $this->math->normalize($data->openingBalance),
                'is_credit_allowed' => $data->isCreditAllowed,
                'is_advance_allowed' => $data->isAdvanceAllowed,
                'notes' => $data->notes,
                'metadata' => $data->metadata,
                'approved_by' => $data->status->value === 'active' ? $data->createdBy : null,
                'approved_at' => $data->status->value === 'active' ? now() : null,
            ]);

            foreach ($data->contacts as $contact) {
                $this->contacts->create($supplier, $contact);
            }
            foreach ($data->addresses as $address) {
                $this->addresses->create($supplier, $address);
            }
            foreach ($data->bankAccounts as $account) {
                $this->bankAccounts->create($supplier, $account);
            }
            $this->categories->assign($supplier, $data->categoryIds);
            foreach ($data->documents as $document) {
                $this->documents->create($supplier, $document);
            }
            foreach ($data->itemMappings as $mapping) {
                $this->itemMappings->create($supplier, $mapping);
            }
            $this->creditProfiles->set(
                $supplier,
                $data->creditProfile ?? new SupplierCreditProfileData(
                    creditLimit: $data->creditLimit,
                ),
            );
            $this->statuses->recordInitial($supplier, $data->createdBy);

            return $supplier->refresh()->load([
                'contacts',
                'addresses',
                'bankAccounts',
                'categories',
                'documents',
                'itemMappings',
                'creditProfile',
                'statusHistories',
            ]);
        });
    }
}
