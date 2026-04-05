<?php
declare(strict_types=1);
namespace Modules\POS\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\POS\Domain\Entities\PosTerminal;
use Modules\POS\Domain\RepositoryInterfaces\PosTerminalRepositoryInterface;
use Modules\POS\Infrastructure\Persistence\Eloquent\Models\PosTerminalModel;

class EloquentPosTerminalRepository implements PosTerminalRepositoryInterface
{
    public function __construct(private readonly PosTerminalModel $model) {}

    public function findById(int $id): ?PosTerminal
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByCode(int $tenantId, string $code): ?PosTerminal
    {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): PosTerminal
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?PosTerminal
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) { return null; }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }

    private function toEntity(PosTerminalModel $m): PosTerminal
    {
        return new PosTerminal(
            $m->id, $m->tenant_id, $m->warehouse_id,
            $m->name, $m->code, $m->description,
            (bool) $m->is_active, $m->created_at, $m->updated_at,
        );
    }
}
