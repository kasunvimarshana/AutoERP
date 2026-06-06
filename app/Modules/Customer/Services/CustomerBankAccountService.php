<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use InvalidArgumentException;
use Modules\Customer\DTOs\CustomerBankAccountData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerBankAccount;
use Modules\Customer\Validators\CustomerValidationService;

final class CustomerBankAccountService
{
    public function __construct(private readonly CustomerValidationService $validator) {}

    public function create(Customer $customer, CustomerBankAccountData $data): CustomerBankAccount
    {
        if (trim($data->bankName) === '' || trim($data->accountName) === '' || trim($data->accountNumber) === '') {
            throw new InvalidArgumentException('Customer bank, account name, and account number are required.');
        }
        if ($data->isPrimary && $customer->bankAccounts()->where('is_primary', true)->exists()) {
            throw new InvalidArgumentException('Customer can have only one primary bank account.');
        }
        if ($customer->bankAccounts()->withTrashed()->where('account_number', $data->accountNumber)->exists()) {
            throw new InvalidArgumentException('Customer bank account number already exists.');
        }
        $this->validator->assertCurrencyActive($data->currencyId);

        return $customer->bankAccounts()->create([
            'tenant_id' => $customer->tenant_id,
            'organization_unit_id' => $customer->organization_unit_id,
            'bank_name' => $data->bankName,
            'branch_name' => $data->branchName,
            'account_name' => $data->accountName,
            'account_number' => $data->accountNumber,
            'swift_code' => $data->swiftCode,
            'iban' => $data->iban,
            'currency_id' => $data->currencyId,
            'is_primary' => $data->isPrimary,
            'is_active' => $data->isActive,
            'notes' => $data->notes,
        ]);
    }

    public function update(
        Customer $customer,
        CustomerBankAccount $account,
        CustomerBankAccountData $data,
    ): CustomerBankAccount {
        $this->assertOwned($customer, $account);
        if (trim($data->bankName) === '' || trim($data->accountName) === '' || trim($data->accountNumber) === '') {
            throw new InvalidArgumentException('Customer bank, account name, and account number are required.');
        }
        if ($data->isPrimary && $customer->bankAccounts()->whereKeyNot($account->getKey())->where('is_primary', true)->exists()) {
            throw new InvalidArgumentException('Customer can have only one primary bank account.');
        }
        if ($customer->bankAccounts()->withTrashed()->whereKeyNot($account->getKey())->where('account_number', $data->accountNumber)->exists()) {
            throw new InvalidArgumentException('Customer bank account number already exists.');
        }
        $this->validator->assertCurrencyActive($data->currencyId);

        $account->fill([
            'bank_name' => $data->bankName,
            'branch_name' => $data->branchName,
            'account_name' => $data->accountName,
            'account_number' => $data->accountNumber,
            'swift_code' => $data->swiftCode,
            'iban' => $data->iban,
            'currency_id' => $data->currencyId,
            'is_primary' => $data->isPrimary,
            'is_active' => $data->isActive,
            'notes' => $data->notes,
        ])->save();

        return $account->refresh()->load('currency');
    }

    public function delete(Customer $customer, CustomerBankAccount $account): void
    {
        $this->assertOwned($customer, $account);
        $account->delete();
    }

    /**
     * @param  list<CustomerBankAccountData>  $bankAccounts
     */
    public function replace(Customer $customer, array $bankAccounts): void
    {
        $customer->bankAccounts()->delete();
        foreach ($bankAccounts as $account) {
            $this->create($customer, $account);
        }
    }

    private function assertOwned(Customer $customer, CustomerBankAccount $account): void
    {
        if ((int) $account->customer_id !== (int) $customer->getKey()) {
            throw new InvalidArgumentException('Customer bank account does not belong to the customer.');
        }
    }
}
