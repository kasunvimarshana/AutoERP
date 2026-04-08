<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;

final class EloquentAccountRepository extends EloquentRepository implements AccountRepositoryInterface
{
    public function __construct(AccountModel $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code, int $tenantId): mixed
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('code', $code)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByType(string $type, int $tenantId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('type', $type)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    /**
     * {@inheritdoc}
     *
     * Returns root-level accounts with their children recursively loaded.
     */
    public function getChartOfAccounts(int $tenantId): Collection
    {
        $all = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return $this->buildTree($all, null);
    }

    /**
     * {@inheritdoc}
     *
     * Applies accounting rules:
     * - Debit side: increases debit-normal (asset/expense), decreases credit-normal accounts
     * - Credit side: increases credit-normal (liability/equity/revenue), decreases debit-normal accounts
     */
    public function updateBalance(int $accountId, float $amount, string $side): void
    {
        $account = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->lockForUpdate()
            ->find($accountId);

        if (! $account) {
            return;
        }

        $isDebitNormal = in_array($account->nature, ['debit'], true);

        $delta = match (true) {
            $side === 'debit' && $isDebitNormal   =>  $amount,
            $side === 'debit' && ! $isDebitNormal  => -$amount,
            $side === 'credit' && $isDebitNormal   => -$amount,
            default                                =>  $amount,
        };

        $account->increment('current_balance', $delta);
    }

    /**
     * {@inheritdoc}
     */
    public function findByUuid(string $uuid): mixed
    {
        return $this->model->newQuery()
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findRootAccounts(int $tenantId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    /**
     * Recursively build a hierarchy tree from a flat collection.
     */
    private function buildTree(\Illuminate\Support\Collection $all, ?int $parentId): Collection
    {
        return $all->where('parent_id', $parentId)->map(function ($account) use ($all) {
            $account->children_tree = $this->buildTree($all, $account->id);

            return $account;
        })->values();
    }
}
