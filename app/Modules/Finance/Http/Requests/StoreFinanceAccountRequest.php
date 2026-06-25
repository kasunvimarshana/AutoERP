<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\Enums\NormalBalance;

final class StoreFinanceAccountRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        $tenantId = $this->tenantId();
        $accountId = $this->route('account');

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'account_type_id' => [
                'required',
                'integer',
                Rule::exists('finance_account_types', 'id')
                    ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId)),
            ],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('finance_accounts', 'code')
                    ->where('tenant_id', $tenantId)
                    ->ignore(is_numeric($accountId) ? (int) $accountId : null),
            ],
            'name' => ['required', 'string', 'max:255'],
            'normal_balance' => ['required', Rule::enum(NormalBalance::class)],
            'account_category_id' => ['nullable', 'integer', 'min:1', 'exists:finance_account_categories,id'],
            'parent_id' => ['nullable', 'integer', 'min:1', 'exists:finance_accounts,id'],
            'description' => ['nullable', 'string'],
            'is_control_account' => ['nullable', 'boolean'],
            'is_posting_account' => ['nullable', 'boolean'],
            'is_cash_account' => ['nullable', 'boolean'],
            'is_bank_account' => ['nullable', 'boolean'],
            'is_tax_account' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'opening_balance' => ['nullable', 'decimal:0,6', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toData(): CreateAccountData
    {
        return new CreateAccountData(
            tenantId: $this->tenantId(),
            accountTypeId: (int) $this->input('account_type_id'),
            code: (string) $this->input('code'),
            name: (string) $this->input('name'),
            normalBalance: NormalBalance::from((string) $this->input('normal_balance')),
            organizationUnitId: $this->organizationUnitId(),
            accountCategoryId: $this->filled('account_category_id') ? (int) $this->input('account_category_id') : null,
            parentId: $this->filled('parent_id') ? (int) $this->input('parent_id') : null,
            description: $this->filled('description') ? (string) $this->input('description') : null,
            isControlAccount: $this->boolean('is_control_account'),
            isPostingAccount: $this->boolean('is_posting_account', true),
            isCashAccount: $this->boolean('is_cash_account'),
            isBankAccount: $this->boolean('is_bank_account'),
            isTaxAccount: $this->boolean('is_tax_account'),
            isActive: $this->boolean('is_active', true),
            openingBalance: (string) $this->input('opening_balance', '0.000000'),
            metadata: $this->input('metadata'),
        );
    }
}
