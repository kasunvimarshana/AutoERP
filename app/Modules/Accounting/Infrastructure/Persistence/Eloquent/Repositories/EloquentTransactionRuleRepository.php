<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;
use Modules\Accounting\Domain\Entities\TransactionRule;
use Modules\Accounting\Domain\RepositoryInterfaces\TransactionRuleRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\TransactionRuleModel;
class EloquentTransactionRuleRepository implements TransactionRuleRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?TransactionRule
    {
        $model = TransactionRuleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }
    public function findAll(string $tenantId): array
    {
        return TransactionRuleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('priority')
            ->get()
            ->map(fn(TransactionRuleModel $m) => $this->mapToEntity($m))
            ->all();
    }
    public function findActive(string $tenantId): array
    {
        return TransactionRuleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get()
            ->map(fn(TransactionRuleModel $m) => $this->mapToEntity($m))
            ->all();
    }
    public function save(TransactionRule $rule): void
    {
        /** @var TransactionRuleModel $model */
        $model = TransactionRuleModel::withoutGlobalScopes()->findOrNew($rule->id);
        $model->fill([
            'tenant_id'   => $rule->tenantId,
            'name'        => $rule->name,
            'priority'    => $rule->priority,
            'conditions'  => $rule->conditions,
            'apply_to'    => $rule->applyTo,
            'account_id'  => $rule->accountId,
            'description' => $rule->description,
            'is_active'   => $rule->isActive,
        ]);
        if (! $model->exists) {
            $model->id = $rule->id;
        }
        $model->save();
    }
    public function delete(string $tenantId, string $id): void
    {
        TransactionRuleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }
    private function mapToEntity(TransactionRuleModel $model): TransactionRule
    {
        return new TransactionRule(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            name: (string) $model->name,
            priority: (int) $model->priority,
            conditions: (array) ($model->conditions ?? []),
            applyTo: (string) $model->apply_to,
            accountId: (string) $model->account_id,
            description: $model->description !== null ? (string) $model->description : null,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
