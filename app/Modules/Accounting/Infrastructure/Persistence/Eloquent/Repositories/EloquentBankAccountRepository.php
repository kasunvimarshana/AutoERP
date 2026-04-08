<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;
use Modules\Accounting\Domain\Entities\BankAccount;
use Modules\Accounting\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;
class EloquentBankAccountRepository implements BankAccountRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?BankAccount
    {
        $model = BankAccountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }
    public function findAll(string $tenantId): array
    {
        return BankAccountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(BankAccountModel $m) => $this->mapToEntity($m))
            ->all();
    }
    public function save(BankAccount $account): void
    {
        /** @var BankAccountModel $model */
        $model = BankAccountModel::withoutGlobalScopes()->findOrNew($account->id);
        $model->fill([
            'tenant_id'          => $account->tenantId,
            'account_id'         => $account->accountId,
            'name'               => $account->name,
            'account_type'       => $account->accountType,
            'bank_name'          => $account->bankName,
            'account_number'     => $account->accountNumber,
            'routing_number'     => $account->routingNumber,
            'currency_code'      => $account->currencyCode,
            'current_balance'    => $account->currentBalance,
            'last_reconciled_at' => $account->lastReconciledAt?->format('Y-m-d H:i:s'),
            'is_active'          => $account->isActive,
        ]);
        if (! $model->exists) {
            $model->id = $account->id;
        }
        $model->save();
    }
    public function delete(string $tenantId, string $id): void
    {
        BankAccountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }
    private function mapToEntity(BankAccountModel $model): BankAccount
    {
        return new BankAccount(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            accountId: (string) $model->account_id,
            name: (string) $model->name,
            accountType: (string) $model->account_type,
            bankName: $model->bank_name !== null ? (string) $model->bank_name : null,
            accountNumber: $model->account_number !== null ? (string) $model->account_number : null,
            routingNumber: $model->routing_number !== null ? (string) $model->routing_number : null,
            currencyCode: (string) $model->currency_code,
            currentBalance: (float) $model->current_balance,
            lastReconciledAt: $model->last_reconciled_at,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
