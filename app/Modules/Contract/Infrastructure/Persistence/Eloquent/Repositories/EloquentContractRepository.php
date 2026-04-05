<?php
declare(strict_types=1);
namespace Modules\Contract\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Contract\Domain\Entities\Contract;
use Modules\Contract\Domain\RepositoryInterfaces\ContractRepositoryInterface;
use Modules\Contract\Infrastructure\Persistence\Eloquent\Models\ContractModel;

class EloquentContractRepository implements ContractRepositoryInterface
{
    public function __construct(private readonly ContractModel $model) {}

    public function findById(int $id): ?Contract
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByNumber(int $tenantId, string $number): ?Contract
    {
        $m = $this->model->newQuery()->where('tenant_id', $tenantId)->where('contract_number', $number)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        $q = $this->model->newQuery()->where('tenant_id', $tenantId);
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['type'])) $q->where('type', $filters['type']);
        return $q->orderByDesc('created_at')->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function findExpiring(int $tenantId, \DateTimeInterface $before): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('status', Contract::STATUS_ACTIVE)
            ->where('end_date', '<=', $before->format('Y-m-d'))
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): Contract
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?Contract
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }

    private function toEntity(ContractModel $m): Contract
    {
        return new Contract(
            $m->id, $m->tenant_id, $m->contract_number, $m->type, $m->status,
            $m->title, $m->description, $m->customer_id, $m->supplier_id, $m->owner_id,
            (float) $m->value, $m->currency,
            $m->start_date, $m->end_date, $m->terms,
            (bool) $m->auto_renew, $m->terminated_at,
            $m->created_at, $m->updated_at,
        );
    }
}
