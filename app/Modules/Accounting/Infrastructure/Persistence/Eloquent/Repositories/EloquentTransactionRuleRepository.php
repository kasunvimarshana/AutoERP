<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Accounting\Domain\Entities\TransactionRule;
use Modules\Accounting\Domain\RepositoryInterfaces\TransactionRuleRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\TransactionRuleModel;

class EloquentTransactionRuleRepository implements TransactionRuleRepositoryInterface
{
    public function __construct(private readonly TransactionRuleModel $model) {}

    private function toEntity(TransactionRuleModel $m): TransactionRule
    {
        return new TransactionRule(
            $m->id, $m->tenant_id, $m->name, (bool)$m->is_active,
            (int)$m->priority, (array)($m->conditions ?? []),
            (array)($m->actions ?? []), $m->apply_to,
            (int)($m->match_count ?? 0), $m->created_at, $m->updated_at,
        );
    }

    public function findById(int $id): ?TransactionRule
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findActiveByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): TransactionRule
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?TransactionRule
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function incrementMatchCount(int $id): void
    {
        $this->model->newQuery()->where('id', $id)->increment('match_count');
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool)$m->delete() : false;
    }
}
