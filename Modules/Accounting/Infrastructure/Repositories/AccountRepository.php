<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Enums\AccountStatus;
use Modules\Accounting\Domain\Enums\AccountType;
use Modules\Accounting\Infrastructure\Models\AccountModel;

class AccountRepository extends BaseRepository implements AccountRepositoryInterface
{
    protected function model(): string
    {
        return AccountModel::class;
    }

    public function findById(int $id, int $tenantId): ?Account
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByCode(string $code, int $tenantId): ?Account
    {
        $model = $this->newQuery()
            ->where('code', $code)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('code')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (AccountModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function save(Account $account): Account
    {
        if ($account->id !== null) {
            $model = $this->newQuery()
                ->where('id', $account->id)
                ->where('tenant_id', $account->tenantId)
                ->firstOrFail();
        } else {
            $model = new AccountModel;
            $model->tenant_id = $account->tenantId;
        }

        $model->parent_id = $account->parentId;
        $model->code = $account->code;
        $model->name = $account->name;
        $model->type = $account->type->value;
        $model->status = $account->status->value;
        $model->description = $account->description;
        $model->is_system_account = $account->isSystemAccount;
        $model->opening_balance = $account->openingBalance;
        $model->current_balance = $account->currentBalance;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($model === null) {
            throw new \DomainException("Account with ID {$id} not found.");
        }

        $model->delete();
    }

    public function updateBalance(int $accountId, int $tenantId, string $amount, bool $isDebit): void
    {
        $model = $this->newQuery()
            ->where('id', $accountId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($model === null) {
            throw new \DomainException("Account with ID {$accountId} not found.");
        }

        $current = bcadd((string) $model->current_balance, '0', 4);

        if ($isDebit) {
            $model->current_balance = bcadd($current, $amount, 4);
        } else {
            $model->current_balance = bcsub($current, $amount, 4);
        }

        $model->save();
    }

    private function toDomain(AccountModel $model): Account
    {
        return new Account(
            id: $model->id,
            tenantId: $model->tenant_id,
            parentId: $model->parent_id,
            code: $model->code,
            name: $model->name,
            type: AccountType::from($model->type),
            status: AccountStatus::from($model->status),
            description: $model->description,
            isSystemAccount: (bool) $model->is_system_account,
            openingBalance: bcadd((string) $model->opening_balance, '0', 4),
            currentBalance: bcadd((string) $model->current_balance, '0', 4),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
