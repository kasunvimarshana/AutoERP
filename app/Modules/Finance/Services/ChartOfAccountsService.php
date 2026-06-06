<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Validators\FinanceValidationService;

final class ChartOfAccountsService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinanceValidationService $validator,
    ) {}

    public function createAccount(CreateAccountData $data): FinanceAccount
    {
        $this->validator->validateAccountCreation($data);

        return FinanceAccount::query()->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'account_type_id' => $data->accountTypeId,
            'account_category_id' => $data->accountCategoryId,
            'parent_id' => $data->parentId,
            'code' => $data->code,
            'name' => $data->name,
            'description' => $data->description,
            'normal_balance' => $data->normalBalance->value,
            'is_control_account' => $data->isControlAccount,
            'is_posting_account' => $data->isPostingAccount,
            'is_cash_account' => $data->isCashAccount,
            'is_bank_account' => $data->isBankAccount,
            'is_tax_account' => $data->isTaxAccount,
            'is_system' => $data->isSystem,
            'is_active' => $data->isActive,
            'opening_balance' => $this->math->normalize($data->openingBalance),
            'current_balance' => $this->math->normalize($data->openingBalance),
            'metadata' => $data->metadata,
        ]);
    }

    public function assertPostable(FinanceAccount $account): void
    {
        if (! (bool) $account->is_active) {
            throw new \InvalidArgumentException('Cannot post to inactive account.');
        }

        if (! (bool) $account->is_posting_account) {
            throw new \InvalidArgumentException('Cannot post to non-posting account.');
        }
    }
}
