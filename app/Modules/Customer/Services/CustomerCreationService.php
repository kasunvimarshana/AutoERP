<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\DTOs\CreateCustomerData;
use Modules\Customer\DTOs\CustomerCreditProfileData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Validators\CustomerValidationService;

final class CustomerCreationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly CustomerValidationService $validator,
        private readonly CustomerNumberService $numbers,
        private readonly CustomerContactService $contacts,
        private readonly CustomerAddressService $addresses,
        private readonly CustomerBankAccountService $bankAccounts,
        private readonly CustomerCategoryService $categories,
        private readonly CustomerDocumentService $documents,
        private readonly CustomerCreditProfileService $creditProfiles,
        private readonly CustomerStatusService $statuses,
    ) {}

    public function create(CreateCustomerData $data): Customer
    {
        $this->validator->validateCreate($data);

        return DB::transaction(function () use ($data): Customer {
            $customer = Customer::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'customer_number' => $data->customerNumber ?? $this->numbers->next($data->tenantId),
                'code' => $data->code,
                'name' => $data->name,
                'legal_name' => $data->legalName,
                'display_name' => $data->displayName,
                'customer_type' => $data->customerType,
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
                'is_tax_exempt' => $data->isTaxExempt,
                'marketing_consent' => $data->marketingConsent,
                'preferred_communication_channel' => $data->preferredCommunicationChannel,
                'notes' => $data->notes,
                'metadata' => $data->metadata,
                'approved_by' => $data->status->value === 'active' ? $data->createdBy : null,
                'approved_at' => $data->status->value === 'active' ? now() : null,
            ]);

            foreach ($data->contacts as $contact) {
                $this->contacts->create($customer, $contact);
            }
            foreach ($data->addresses as $address) {
                $this->addresses->create($customer, $address);
            }
            foreach ($data->bankAccounts as $account) {
                $this->bankAccounts->create($customer, $account);
            }
            $this->categories->assign($customer, $data->categoryIds);
            foreach ($data->documents as $document) {
                $this->documents->create($customer, $document);
            }
            $this->creditProfiles->set(
                $customer,
                $data->creditProfile ?? new CustomerCreditProfileData(
                    creditLimit: $data->creditLimit,
                ),
            );
            $this->statuses->recordInitial($customer, $data->createdBy);

            return $customer->refresh()->load([
                'defaultCurrency',
                'contacts',
                'addresses',
                'bankAccounts.currency',
                'categories.parent',
                'documents',
                'creditProfile',
                'statusHistories',
            ]);
        });
    }
}
