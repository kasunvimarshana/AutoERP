<?php
declare(strict_types=1);
namespace Modules\POS\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\POS\Domain\Entities\PosSession;
use Modules\POS\Domain\RepositoryInterfaces\PosSessionRepositoryInterface;
use Modules\POS\Infrastructure\Persistence\Eloquent\Models\PosSessionModel;

class EloquentPosSessionRepository implements PosSessionRepositoryInterface
{
    public function __construct(private readonly PosSessionModel $model) {}

    public function findById(int $id): ?PosSession
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findOpenByTerminal(int $terminalId): ?PosSession
    {
        $m = $this->model->newQuery()
            ->where('terminal_id', $terminalId)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByTenant(int $tenantId, int $perPage = 20, int $page = 1): array
    {
        $total = $this->model->newQuery()->where('tenant_id', $tenantId)->count();
        $data  = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('opened_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
        return ['data' => $data, 'total' => $total];
    }

    public function create(array $data): PosSession
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?PosSession
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) { return null; }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    private function toEntity(PosSessionModel $m): PosSession
    {
        return new PosSession(
            $m->id, $m->tenant_id, $m->terminal_id, $m->cashier_id,
            $m->status, (float) $m->opening_balance,
            $m->closing_balance !== null ? (float) $m->closing_balance : null,
            $m->notes, $m->opened_at, $m->closed_at,
            $m->created_at, $m->updated_at,
        );
    }
}
