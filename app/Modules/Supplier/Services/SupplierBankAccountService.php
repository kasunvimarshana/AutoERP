<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use InvalidArgumentException;
use Modules\Supplier\DTOs\SupplierBankAccountData;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierBankAccount;
use Modules\Supplier\Validators\SupplierValidationService;

final class SupplierBankAccountService
{
    public function __construct(private readonly SupplierValidationService $validator) {}

    public function create(Supplier $supplier, SupplierBankAccountData $data): SupplierBankAccount
    {
        if (trim($data->bankName) === '' || trim($data->accountName) === '' || trim($data->accountNumber) === '') {
            throw new InvalidArgumentException('Supplier bank, account name, and account number are required.');
        }
        if ($data->isPrimary && $supplier->bankAccounts()->where('is_primary', true)->exists()) {
            throw new InvalidArgumentException('Supplier can have only one primary bank account.');
        }
        if ($supplier->bankAccounts()->withTrashed()->where('account_number', $data->accountNumber)->exists()) {
            throw new InvalidArgumentException('Supplier bank account number already exists.');
        }
        $this->validator->assertCurrencyActive($data->currencyId);

        return $supplier->bankAccounts()->create([
            'tenant_id' => $supplier->tenant_id,
            'organization_unit_id' => $supplier->organization_unit_id,
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
        Supplier $supplier,
        SupplierBankAccount $account,
        SupplierBankAccountData $data,
    ): SupplierBankAccount {
        $this->assertOwned($supplier, $account);
        if (trim($data->bankName) === '' || trim($data->accountName) === '' || trim($data->accountNumber) === '') {
            throw new InvalidArgumentException('Supplier bank, account name, and account number are required.');
        }
        if ($data->isPrimary && $supplier->bankAccounts()->whereKeyNot($account->getKey())->where('is_primary', true)->exists()) {
            throw new InvalidArgumentException('Supplier can have only one primary bank account.');
        }
        if ($supplier->bankAccounts()->withTrashed()->whereKeyNot($account->getKey())->where('account_number', $data->accountNumber)->exists()) {
            throw new InvalidArgumentException('Supplier bank account number already exists.');
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

    public function delete(Supplier $supplier, SupplierBankAccount $account): void
    {
        $this->assertOwned($supplier, $account);
        $account->delete();
    }

    /**
     * @param  list<SupplierBankAccountData>  $bankAccounts
     */
    public function replace(Supplier $supplier, array $bankAccounts): void
    {
        $supplier->bankAccounts()->delete();
        foreach ($bankAccounts as $account) {
            $this->create($supplier, $account);
        }
    }

    private function assertOwned(Supplier $supplier, SupplierBankAccount $account): void
    {
        if ((int) $account->supplier_id !== (int) $supplier->getKey()) {
            throw new InvalidArgumentException('Supplier bank account does not belong to the supplier.');
        }
    }
}
