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
}
