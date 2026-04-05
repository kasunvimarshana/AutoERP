<?php
declare(strict_types=1);
namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\RepositoryInterfaces\AuditLogRepositoryInterface;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;

class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function __construct(private readonly AuditLogModel $model) {}

    public function findById(int $id): ?AuditLog
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 50, int $page = 1): array
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);

        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }
        if (!empty($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $total = $query->count();
        $data  = $query->orderByDesc('created_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();

        return ['data' => $data, 'total' => $total];
    }

    public function findByEntity(int $tenantId, string $entityType, string $entityId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): AuditLog
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function deleteOlderThan(\DateTimeInterface $before): int
    {
        return $this->model->newQuery()
            ->where('created_at', '<', $before->format('Y-m-d H:i:s'))
            ->delete();
    }

    private function toEntity(AuditLogModel $m): AuditLog
    {
        return new AuditLog(
            $m->id, $m->tenant_id, $m->user_id,
            $m->event, $m->entity_type, $m->entity_id,
            $m->old_values, $m->new_values,
            $m->ip_address, $m->user_agent, $m->url,
            $m->created_at,
        );
    }
}
