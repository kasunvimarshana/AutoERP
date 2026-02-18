<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\Account;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Core\Repositories\BaseRepository;

/**
 * Account Repository Implementation
 *
 * Handles all account data access operations.
 */
class AccountRepository extends BaseRepository implements AccountRepositoryInterface
{
    /**
     * AccountRepository constructor.
     */
    public function __construct(Account $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByAccountNumber(string $accountNumber): ?Account
    {
        return $this->model->where('account_number', $accountNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)->orderBy('account_number')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveAccounts(): Collection
    {
        return $this->model->where('is_active', true)->orderBy('account_number')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getChartOfAccounts(): Collection
    {
        return $this->model
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('account_number')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getChildAccounts(int $parentId): Collection
    {
        return $this->model->where('parent_id', $parentId)->orderBy('account_number')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('account_number', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->orderBy('account_number')
            ->get();
    }
}
