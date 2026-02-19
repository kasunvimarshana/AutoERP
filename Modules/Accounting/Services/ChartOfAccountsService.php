<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Modules\Accounting\Enums\AccountStatus;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Repositories\AccountRepository;

/**
 * Chart of Accounts Service
 *
 * Manages the chart of accounts structure and hierarchy
 */
class ChartOfAccountsService
{
    public function __construct(
        private AccountRepository $accountRepository
    ) {}

    /**
     * Get full chart of accounts
     */
    public function getChartOfAccounts(): array
    {
        $accounts = $this->accountRepository->getChartOfAccounts();

        return $this->buildAccountTree($accounts);
    }

    /**
     * Get accounts by type
     */
    public function getAccountsByType(AccountType $type): array
    {
        $accounts = Account::where('type', $type)
            ->where('status', AccountStatus::Active)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('code')
            ->get();

        return $this->buildAccountTree($accounts);
    }

    /**
     * Get account hierarchy
     */
    public function getAccountHierarchy(string $accountId): array
    {
        $account = $this->accountRepository->findOrFail($accountId);
        $hierarchy = [];

        $current = $account;
        while ($current) {
            array_unshift($hierarchy, [
                'id' => $current->id,
                'code' => $current->code,
                'name' => $current->name,
                'type' => $current->type,
            ]);
            $current = $current->parent;
        }

        return $hierarchy;
    }

    /**
     * Get account with all descendants
     */
    public function getAccountWithDescendants(string $accountId): array
    {
        $account = $this->accountRepository->findOrFail($accountId);

        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type,
            'status' => $account->status,
            'descendants' => $this->buildDescendantsTree($account->descendants()),
        ];
    }

    /**
     * Build account tree structure
     */
    protected function buildAccountTree($accounts): array
    {
        $tree = [];

        foreach ($accounts as $account) {
            $tree[] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'status' => $account->status,
                'normal_balance' => $account->normal_balance,
                'is_parent' => $account->children->isNotEmpty(),
                'children' => $account->children->isNotEmpty()
                    ? $this->buildAccountTree($account->children)
                    : [],
            ];
        }

        return $tree;
    }

    /**
     * Build descendants tree
     */
    protected function buildDescendantsTree(array $descendants): array
    {
        $tree = [];

        foreach ($descendants as $account) {
            $tree[] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'status' => $account->status,
            ];
        }

        return $tree;
    }

    /**
     * Validate account hierarchy
     */
    public function validateAccountHierarchy(string $accountId, ?string $parentId): bool
    {
        if (! $parentId) {
            return true;
        }

        $account = $this->accountRepository->findOrFail($accountId);
        $parent = $this->accountRepository->findOrFail($parentId);

        if ($account->type !== $parent->type) {
            return false;
        }

        $descendants = collect($account->descendants())->pluck('id');
        if ($descendants->contains($parentId)) {
            return false;
        }

        return true;
    }

    /**
     * Get leaf accounts (detail accounts)
     */
    public function getLeafAccounts(?AccountType $type = null): array
    {
        $query = Account::whereDoesntHave('children')
            ->where('status', AccountStatus::Active);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('code')->get()->map(function ($account) {
            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'normal_balance' => $account->normal_balance,
            ];
        })->toArray();
    }

    /**
     * Get bank accounts
     */
    public function getBankAccounts(): array
    {
        return Account::where('is_bank_account', true)
            ->where('status', AccountStatus::Active)
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                ];
            })
            ->toArray();
    }

    /**
     * Get reconcilable accounts
     */
    public function getReconcilableAccounts(): array
    {
        return Account::where('is_reconcilable', true)
            ->where('status', AccountStatus::Active)
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                ];
            })
            ->toArray();
    }
}
