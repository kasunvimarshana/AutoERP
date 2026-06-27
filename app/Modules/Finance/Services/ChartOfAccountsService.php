<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Validators\FinanceValidationService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class ChartOfAccountsService
{
    public function __construct(private readonly FinanceValidationService $validator) {}

    public function createAccount(CreateAccountData $data): FinanceAccount
    {
        return DB::transaction(function () use ($data): FinanceAccount {
            $this->validator->validateAccountCreation($data);
            $this->lockParentChain($data->tenantId, $data->parentId);

            return FinanceAccount::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'account_type_id' => $data->accountTypeId,
                'account_category_id' => $data->accountCategoryId,
                'parent_id' => $data->parentId,
                'code' => trim($data->code),
                'name' => trim($data->name),
                'description' => $data->description,
                'normal_balance' => $data->normalBalance->value,
                'is_control_account' => $data->isControlAccount,
                'is_posting_account' => $data->isPostingAccount,
                'is_cash_account' => $data->isCashAccount,
                'is_bank_account' => $data->isBankAccount,
                'is_tax_account' => $data->isTaxAccount,
                'is_system' => $data->isSystem,
                'is_active' => $data->isActive,
                'metadata' => $data->metadata,
            ]);
        }, 3);
    }

    public function updateAccount(FinanceAccount $account, CreateAccountData $data): FinanceAccount
    {
        return DB::transaction(function () use ($account, $data): FinanceAccount {
            $account = FinanceAccount::query()->lockForUpdate()->findOrFail($account->getKey());
            if ($data->expectedVersion === null || $data->expectedVersion !== (int) $account->row_version) {
                throw new ConflictHttpException('Finance account was changed by another request.');
            }
            $this->validator->validateAccountCreation($data, (int) $account->getKey());
            if ((int) $account->tenant_id !== $data->tenantId
                || $account->organization_unit_id !== $data->organizationUnitId) {
                throw new \InvalidArgumentException('Finance account scope cannot be changed.');
            }

            $this->lockParentChain($data->tenantId, $data->parentId);
            $this->assertNoHierarchyCycle($account, $data->parentId);
            if ($account->ledgerEntries()->exists() && $account->normal_balance !== $data->normalBalance) {
                throw new \InvalidArgumentException('An account with posted ledger entries cannot change normal balance.');
            }

            $account->forceFill([
                'row_version' => (int) $account->row_version + 1,
                'account_type_id' => $data->accountTypeId,
                'account_category_id' => $data->accountCategoryId,
                'parent_id' => $data->parentId,
                'code' => trim($data->code),
                'name' => trim($data->name),
                'description' => $data->description,
                'normal_balance' => $data->normalBalance->value,
                'is_control_account' => $data->isControlAccount,
                'is_posting_account' => $data->isPostingAccount,
                'is_cash_account' => $data->isCashAccount,
                'is_bank_account' => $data->isBankAccount,
                'is_tax_account' => $data->isTaxAccount,
                'is_active' => $data->isActive,
                'metadata' => $data->metadata,
            ])->save();

            return $account->refresh();
        }, 3);
    }

    public function assertPostable(FinanceAccount $account): void
    {
        if (! $account->is_active) {
            throw new \InvalidArgumentException('Cannot post to inactive account.');
        }
        if (! $account->is_posting_account) {
            throw new \InvalidArgumentException('Cannot post to non-posting account.');
        }
    }

    private function lockParentChain(int $tenantId, ?int $parentId): void
    {
        $currentId = $parentId;
        $visited = [];
        while ($currentId !== null) {
            if (isset($visited[$currentId])) {
                throw new ConflictHttpException('Finance account hierarchy contains a cycle.');
            }
            $visited[$currentId] = true;
            $parent = FinanceAccount::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($currentId)
                ->lockForUpdate()
                ->firstOrFail();
            $currentId = $parent->parent_id === null ? null : (int) $parent->parent_id;
        }
    }

    private function assertNoHierarchyCycle(FinanceAccount $account, ?int $parentId): void
    {
        $currentId = $parentId;
        while ($currentId !== null) {
            if ($currentId === (int) $account->getKey()) {
                throw new ConflictHttpException('Finance account cannot be moved below itself or one of its descendants.');
            }
            $parent = FinanceAccount::query()->findOrFail($currentId);
            $currentId = $parent->parent_id === null ? null : (int) $parent->parent_id;
        }
    }
}
