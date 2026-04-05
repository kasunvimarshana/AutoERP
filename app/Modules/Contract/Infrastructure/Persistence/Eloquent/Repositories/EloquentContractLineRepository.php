<?php
declare(strict_types=1);
namespace Modules\Contract\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Contract\Domain\Entities\ContractLine;
use Modules\Contract\Domain\RepositoryInterfaces\ContractLineRepositoryInterface;
use Modules\Contract\Infrastructure\Persistence\Eloquent\Models\ContractLineModel;

class EloquentContractLineRepository implements ContractLineRepositoryInterface
{
    public function __construct(private readonly ContractLineModel $model) {}

    public function findById(int $id): ?ContractLine
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByContract(int $contractId): array
    {
        return $this->model->newQuery()->where('contract_id', $contractId)
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): ContractLine
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?ContractLine
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

    private function toEntity(ContractLineModel $m): ContractLine
    {
        return new ContractLine(
            $m->id, $m->contract_id, $m->description, $m->product_id,
            (float) $m->quantity, (float) $m->unit_price, (float) $m->total_price,
            $m->due_date, (bool) $m->is_delivered, $m->delivered_at, $m->created_at,
        );
    }
}
