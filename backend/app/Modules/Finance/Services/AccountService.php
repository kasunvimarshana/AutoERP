<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\DTOs\CreateAccountDTO;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Repositories\AccountRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\Auth;

/**
 * Account Service
 *
 * Handles business logic for chart of accounts management.
 */
class AccountService extends BaseService
{
    public function __construct(
        protected AccountRepository $accountRepository
    ) {}

    public function getAllAccounts(int $perPage = 15)
    {
        return $this->accountRepository->paginate($perPage);
    }

    public function createAccount(CreateAccountDTO $dto): Account
    {
        return $this->transaction(function () use ($dto) {
            $this->logInfo('Creating new account', ['code' => $dto->code]);

            // Check if code already exists
            if ($this->accountRepository->findByCode($dto->code)) {
                throw new \InvalidArgumentException("Account with code '{$dto->code}' already exists");
            }

            // Validate parent account if provided
            if ($dto->parentId) {
                $parent = $this->accountRepository->findOrFail($dto->parentId);

                // Validate parent has same account type
                if ($parent->type !== $dto->type) {
                    throw new \InvalidArgumentException(
                        'Parent account must have the same type as the child account'
                    );
                }
            }

            $accountData = $dto->toArray();
            $accountData['tenant_id'] = Auth::user()->tenant_id;

            $account = $this->accountRepository->create($accountData);

            $this->logInfo('Account created successfully', ['id' => $account->id]);

            return $account->load(['currency', 'parent', 'children']);
        });
    }

    public function updateAccount(int $id, CreateAccountDTO $dto): Account
    {
        return $this->transaction(function () use ($id, $dto) {
            $account = $this->accountRepository->findOrFail($id);

            $this->logInfo('Updating account', ['id' => $id]);

            // Check if code changed and already exists
            if ($account->code !== $dto->code && $this->accountRepository->findByCode($dto->code)) {
                throw new \InvalidArgumentException("Account with code '{$dto->code}' already exists");
            }

            // Validate parent account if provided
            if ($dto->parentId) {
                if ($dto->parentId === $id) {
                    throw new \InvalidArgumentException('An account cannot be its own parent');
                }

                $parent = $this->accountRepository->findOrFail($dto->parentId);

                if ($parent->type !== $dto->type) {
                    throw new \InvalidArgumentException(
                        'Parent account must have the same type as the child account'
                    );
                }

                // Check if parent is a descendant of this account
                if ($this->isDescendant($parent, $id)) {
                    throw new \InvalidArgumentException(
                        'Cannot set a descendant account as parent'
                    );
                }
            }

            $this->accountRepository->update($id, $dto->toArray());

            $account->refresh();
            $account->load(['currency', 'parent', 'children']);

            $this->logInfo('Account updated successfully', ['id' => $id]);

            return $account;
        });
    }

    public function deleteAccount(int $id): bool
    {
        $account = $this->accountRepository->findOrFail($id);

        // Check if account has children
        if ($account->children()->count() > 0) {
            throw new \InvalidArgumentException('Cannot delete account with child accounts');
        }

        // Check if account has journal entries
        if ($account->journalEntryLines()->count() > 0) {
            throw new \InvalidArgumentException('Cannot delete account with journal entries');
        }

        $this->logInfo('Deleting account', ['id' => $id]);

        $result = $this->accountRepository->delete($id);

        if ($result) {
            $this->logInfo('Account deleted successfully', ['id' => $id]);
        }

        return $result;
    }

    public function getAccountById(int $id): Account
    {
        return $this->accountRepository->findWithRelations($id);
    }

    public function getAccountByCode(string $code): ?Account
    {
        return $this->accountRepository->findByCode($code);
    }

    public function getAccountsByType(AccountType $type)
    {
        return $this->accountRepository->getByType($type);
    }

    public function getRootAccounts()
    {
        return $this->accountRepository->getRootAccounts();
    }

    public function getActiveAccounts()
    {
        return $this->accountRepository->getActiveAccounts();
    }

    public function searchAccounts(string $search)
    {
        return $this->accountRepository->search($search);
    }

    public function getAccountBalance(int $accountId, ?string $endDate = null): float
    {
        $account = $this->accountRepository->findOrFail($accountId);
        $balance = $account->opening_balance;

        $query = $account->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($endDate) {
                $q->posted();
                if ($endDate) {
                    $q->where('entry_date', '<=', $endDate);
                }
            });

        $totalDebit = $query->sum('debit');
        $totalCredit = $query->sum('credit');

        if ($account->type->isDebitNormal()) {
            $balance += ($totalDebit - $totalCredit);
        } else {
            $balance += ($totalCredit - $totalDebit);
        }

        return $balance;
    }

    public function updateAccountBalance(int $accountId): void
    {
        $balance = $this->getAccountBalance($accountId);
        $this->accountRepository->update($accountId, ['current_balance' => $balance]);
    }

    protected function isDescendant(Account $parent, int $accountId): bool
    {
        if ($parent->id === $accountId) {
            return true;
        }

        if ($parent->parent_id) {
            $grandParent = $this->accountRepository->find($parent->parent_id);

            return $this->isDescendant($grandParent, $accountId);
        }

        return false;
    }
}
