<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\DTOs\SupplierBankAccountData;

abstract class SupplierBankAccountRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'bank_name' => ['required', 'string', 'max:255'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'swift_code' => ['nullable', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:80'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): SupplierBankAccountData
    {
        return new SupplierBankAccountData(
            bankName: (string) $this->input('bank_name'),
            accountName: (string) $this->input('account_name'),
            accountNumber: (string) $this->input('account_number'),
            branchName: $this->nullableString('branch_name'),
            swiftCode: $this->nullableString('swift_code'),
            iban: $this->nullableString('iban'),
            currencyId: $this->filled('currency_id') ? (int) $this->input('currency_id') : null,
            isPrimary: $this->boolean('is_primary'),
            isActive: $this->boolean('is_active', true),
            notes: $this->nullableString('notes'),
        );
    }

    private function nullableString(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
