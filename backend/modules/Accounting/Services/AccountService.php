<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Repositories\AccountRepository;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;

/**
 * Account Service
 *
 * Handles all business logic for chart of accounts management.
 */
class AccountService extends BaseService
{
    public function __construct(
        TenantContext $tenantContext,
        protected AccountRepository $repository
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Get all accounts with optional filters.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = $this->repository->query();

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('code', 'like', "%{$filters['search']}%")
                    ->orWhere('name', 'like', "%{$filters['search']}%");
            });
        }

        $query->with(['parent', 'children']);
        $query->orderBy('code');

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get account hierarchy.
     */
    public function getAccountTree(): Collection
    {
        return $this->repository->getRootAccounts()->load('children');
    }

    /**
     * Create a new account.
     */
    public function create(array $data): Account
    {
        return DB::transaction(function () use ($data) {
            // Validate account code uniqueness
            if ($this->repository->findByCode($data['code'])) {
                throw new \Exception("Account code {$data['code']} already exists.");
            }

            // Set defaults
            $data['balance'] = $data['balance'] ?? 0;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_system'] = $data['is_system'] ?? false;
            $data['currency_code'] = $data['currency_code'] ?? config('app.default_currency', 'USD');

            return $this->repository->create($data);
        });
    }

    /**
     * Update an existing account.
     */
    public function update(string $id, array $data): Account
    {
        return DB::transaction(function () use ($id, $data) {
            $account = $this->repository->findOrFail($id);

            // Prevent editing system accounts
            if ($account->is_system && isset($data['code'])) {
                throw new \Exception('Cannot modify code of system account.');
            }

            // Validate code uniqueness if changing
            if (isset($data['code']) && $data['code'] !== $account->code) {
                if ($this->repository->findByCode($data['code'])) {
                    throw new \Exception("Account code {$data['code']} already exists.");
                }
            }

            $account->update($data);

            return $account->load(['parent', 'children']);
        });
    }

    /**
     * Delete an account.
     */
    public function delete(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $account = $this->repository->findOrFail($id);

            // Prevent deleting system accounts
            if ($account->is_system) {
                throw new \Exception('Cannot delete system account.');
            }

            // Check if account has children
            if ($account->children()->count() > 0) {
                throw new \Exception('Cannot delete account with child accounts.');
            }

            // Check if account has transactions
            if ($account->journalEntryLines()->count() > 0) {
                throw new \Exception('Cannot delete account with existing transactions.');
            }

            return $this->repository->delete($id);
        });
    }

    /**
     * Update account balance.
     */
    public function updateBalance(string $accountId, float $amount): Account
    {
        return DB::transaction(function () use ($accountId, $amount) {
            $account = $this->repository->findOrFail($accountId);
            $account->balance += $amount;
            $account->save();

            return $account;
        });
    }
}
