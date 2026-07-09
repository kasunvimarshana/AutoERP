<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Supplier\DTOs\UpdateSupplierData;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Validators\SupplierValidationService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class SupplierUpdateService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SupplierValidationService $validator,
        private readonly SupplierContactService $contacts,
        private readonly SupplierAddressService $addresses,
        private readonly SupplierBankAccountService $bankAccounts,
        private readonly SupplierCategoryService $categories,
        private readonly SupplierDocumentService $documents,
        private readonly SupplierItemMappingService $itemMappings,
        private readonly SupplierCreditProfileService $creditProfiles,
    ) {}

    public function update(Supplier $supplier, UpdateSupplierData $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data): Supplier {
            $supplier = Supplier::query()
                ->whereKey($supplier->getKey())
                ->where('tenant_id', (int) $supplier->tenant_id)
                ->when(
                    $supplier->organization_unit_id === null,
                    static fn ($query) => $query->whereNull('organization_unit_id'),
                    static fn ($query) => $query->where('organization_unit_id', (int) $supplier->organization_unit_id),
                )
                ->lockForUpdate()
                ->firstOrFail();

            if ($data->rowVersion !== (int) $supplier->row_version) {
                throw new ConflictHttpException('Supplier was changed by someone else. Reload before saving.');
            }

            if ($supplier->status === SupplierStatus::Blacklisted) {
                throw new InvalidArgumentException('Blacklisted supplier master data cannot be updated directly.');
            }

            $this->validator->validateUpdate($supplier, $data);

            $attributes = [];
            foreach ([
                'organization_unit_id' => $data->organizationUnitId,
                'code' => $data->code,
                'name' => $data->name,
                'legal_name' => $data->legalName,
                'display_name' => $data->displayName,
                'supplier_type' => $data->supplierType,
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
                'credit_limit' => $data->creditProfile !== null
                    ? $this->math->normalize($data->creditProfile->creditLimit)
                    : ($data->creditLimit !== null ? $this->math->normalize($data->creditLimit) : null),
                'is_credit_allowed' => $data->isCreditAllowed,
                'is_advance_allowed' => $data->isAdvanceAllowed,
                'notes' => $data->notes,
                'metadata' => $data->metadata,
            ] as $key => $value) {
                $requestKey = $key;
                if (in_array($requestKey, $data->provided, true)) {
                    $attributes[$key] = $value;
                }
            }
            $attributes['row_version'] = ((int) $supplier->row_version) + 1;

            $supplier->fill($attributes);
            $supplier->save();

            if ($data->contacts !== null) {
                $this->contacts->replace($supplier, $data->contacts);
            }
            if ($data->addresses !== null) {
                $this->addresses->replace($supplier, $data->addresses);
            }
            if ($data->bankAccounts !== null) {
                $this->bankAccounts->replace($supplier, $data->bankAccounts);
            }
            if ($data->categoryIds !== null) {
                $this->categories->assign($supplier, $data->categoryIds);
            }
            if ($data->documents !== null) {
                $this->documents->replace($supplier, $data->documents);
            }
            if ($data->itemMappings !== null) {
                $this->itemMappings->replace($supplier, $data->itemMappings);
            }
            if ($data->creditProfile !== null) {
                $this->creditProfiles->set($supplier, $data->creditProfile);
            }

            return $supplier->refresh()->load([
                'contacts',
                'addresses',
                'bankAccounts',
                'categories',
                'documents',
                'itemMappings',
                'creditProfile',
            ]);
        }, 3);
    }
}
