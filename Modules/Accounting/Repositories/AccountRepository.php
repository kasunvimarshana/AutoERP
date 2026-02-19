<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Modules\Accounting\Enums\AccountStatus;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Exceptions\AccountNotFoundException;
use Modules\Accounting\Models\Account;
use Modules\Core\Repositories\BaseRepository;

class AccountRepository extends BaseRepository
{
    public function __construct(Account $model)
    {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return Account::class;
    }

    protected function getNotFoundExceptionClass(): string
    {
        return AccountNotFoundException::class;
    }

    public function findByCode(string $code): ?Account
    {
        return $this->model->where('code', $code)->first();
    }

    public function findByCodeOrFail(string $code): Account
    {
        $account = $this->findByCode($code);

        if (! $account) {
            throw new AccountNotFoundException("Account with code {$code} not found");
        }

        return $account;
    }

    public function getByType(AccountType $type, int $perPage = 15)
    {
        return $this->model->where('type', $type)->latest()->paginate($perPage);
    }

    public function getByStatus(AccountStatus $status, int $perPage = 15)
    {
        return $this->model->where('status', $status)->latest()->paginate($perPage);
    }

    public function getParentAccounts()
    {
        return $this->model->whereHas('children')->get();
    }

    public function getLeafAccounts()
    {
        return $this->model->whereDoesntHave('children')->get();
    }

    public function getSystemAccounts()
    {
        return $this->model->where('is_system', true)->get();
    }

    public function getBankAccounts()
    {
        return $this->model->where('is_bank_account', true)->get();
    }

    public function getReconcilableAccounts()
    {
        return $this->model->where('is_reconcilable', true)->get();
    }

    public function getChartOfAccounts()
    {
        return $this->model
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('code')
            ->get();
    }

    /**
     * Find accounts with filters and pagination.
     */
    public function findWithFilters(array $filters, int $perPage = 15)
    {
        $query = $this->model->newQuery()->with(['parent', 'organization']);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (isset($filters['is_bank_account'])) {
            $query->where('is_bank_account', $filters['is_bank_account']);
        }

        if (isset($filters['is_reconcilable'])) {
            $query->where('is_reconcilable', $filters['is_reconcilable']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('code')->paginate($perPage);
    }
}
