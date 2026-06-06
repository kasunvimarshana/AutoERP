<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\DTOs\UpdateCustomerData;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Customer\Validators\CustomerValidationService;

final class CustomerUpdateService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly CustomerValidationService $validator,
        private readonly CustomerContactService $contacts,
        private readonly CustomerAddressService $addresses,
        private readonly CustomerBankAccountService $bankAccounts,
        private readonly CustomerCategoryService $categories,
        private readonly CustomerDocumentService $documents,
        private readonly CustomerCreditProfileService $creditProfiles,
    ) {}

    public function update(Customer $customer, UpdateCustomerData $data): Customer
    {
        if ($customer->status === CustomerStatus::Blacklisted) {
            throw new InvalidArgumentException('Blacklisted customer master data cannot be updated directly.');
        }

        $this->validator->validateUpdate($customer, $data);

        return DB::transaction(function () use ($customer, $data): Customer {
            $attributes = [];
            foreach ([
                'organization_unit_id' => $data->organizationUnitId,
                'code' => $data->code,
                'name' => $data->name,
                'legal_name' => $data->legalName,
                'display_name' => $data->displayName,
                'customer_type' => $data->customerType,
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
                'opening_balance' => $data->openingBalance !== null ? $this->math->normalize($data->openingBalance) : null,
                'is_credit_allowed' => $data->isCreditAllowed,
                'is_advance_allowed' => $data->isAdvanceAllowed,
                'is_tax_exempt' => $data->isTaxExempt,
                'marketing_consent' => $data->marketingConsent,
                'preferred_communication_channel' => $data->preferredCommunicationChannel,
                'notes' => $data->notes,
                'metadata' => $data->metadata,
            ] as $key => $value) {
                $requestKey = $key;
                if (in_array($requestKey, $data->provided, true)) {
                    $attributes[$key] = $value;
                }
            }

            $customer->fill($attributes);
            $customer->save();

            if ($data->contacts !== null) {
                $this->contacts->replace($customer, $data->contacts);
            }
            if ($data->addresses !== null) {
                $this->addresses->replace($customer, $data->addresses);
            }
            if ($data->bankAccounts !== null) {
                $this->bankAccounts->replace($customer, $data->bankAccounts);
            }
            if ($data->categoryIds !== null) {
                $this->categories->assign($customer, $data->categoryIds);
            }
            if ($data->documents !== null) {
                $this->documents->replace($customer, $data->documents);
            }
            if ($data->creditProfile !== null) {
                $this->creditProfiles->set($customer, $data->creditProfile);
            }

            return $customer->refresh()->load([
                'contacts',
                'addresses',
                'bankAccounts',
                'categories',
                'documents',
                'creditProfile',
            ]);
        });
    }
}
