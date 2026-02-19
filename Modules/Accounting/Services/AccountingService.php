<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Modules\Accounting\Enums\AccountStatus;
use Modules\Accounting\Events\AccountCreated;
use Modules\Accounting\Events\AccountUpdated;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Repositories\AccountRepository;
use Modules\Core\Helpers\TransactionHelper;

/**
 * Accounting Service
 *
 * Core service for managing accounts and basic accounting operations
 */
class AccountingService
{
    public function __construct(
        private AccountRepository $accountRepository
    ) {}

    /**
     * Create a new account
     */
    public function createAccount(array $data): Account
    {
        return TransactionHelper::execute(function () use ($data) {
            if (empty($data['code'])) {
                $data['code'] = $this->generateAccountCode($data['type']);
            }

            $data['status'] = $data['status'] ?? AccountStatus::Active;

            $account = $this->accountRepository->create($data);

            event(new AccountCreated($account));

            return $account;
        });
    }

    /**
     * Update an account
     */
    public function updateAccount(string $id, array $data): Account
    {
        return TransactionHelper::execute(function () use ($id, $data) {
            $account = $this->accountRepository->update($id, $data);

            event(new AccountUpdated($account));

            return $account;
        });
    }

    /**
     * Activate an account
     */
    public function activateAccount(string $id): Account
    {
        return $this->updateAccount($id, ['status' => AccountStatus::Active]);
    }

    /**
     * Deactivate an account
     */
    public function deactivateAccount(string $id): Account
    {
        return $this->updateAccount($id, ['status' => AccountStatus::Inactive]);
    }

    /**
     * Delete an account
     */
    public function deleteAccount(string $id): bool
    {
        $account = $this->accountRepository->findOrFail($id);

        if ($account->is_system) {
            throw new \InvalidArgumentException('System accounts cannot be deleted');
        }

        if ($account->journalLines()->exists()) {
            throw new \InvalidArgumentException('Cannot delete account with journal entries');
        }

        if ($account->children()->exists()) {
            throw new \InvalidArgumentException('Cannot delete parent account with child accounts');
        }

        return TransactionHelper::execute(function () use ($account) {
            return $this->accountRepository->delete($account->id);
        });
    }

    /**
     * Generate account code
     */
    protected function generateAccountCode(string $type): string
    {
        $prefix = config('accounting.account_code_prefix', 'ACC-');
        $length = config('accounting.account_code_length', 10);

        $lastAccount = Account::where('code', 'like', $prefix.'%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastAccount) {
            $lastNumber = (int) str_replace($prefix, '', $lastAccount->code);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.str_pad((string) $newNumber, $length - strlen($prefix), '0', STR_PAD_LEFT);
    }

    /**
     * Get account balance
     */
    public function getAccountBalance(string $accountId, ?string $asOfDate = null): string
    {
        $account = $this->accountRepository->findOrFail($accountId);

        $query = $account->journalLines()
            ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                $q->where('status', 'posted');
                if ($asOfDate) {
                    $q->where('entry_date', '<=', $asOfDate);
                }
            });

        $totalDebits = $query->sum('debit');
        $totalCredits = $query->sum('credit');

        if ($account->normal_balance === 'debit') {
            return bcsub($totalDebits, $totalCredits, config('accounting.decimal_scale', 6));
        } else {
            return bcsub($totalCredits, $totalDebits, config('accounting.decimal_scale', 6));
        }
    }
}
