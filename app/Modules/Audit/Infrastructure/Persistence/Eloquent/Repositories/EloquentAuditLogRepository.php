<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\RepositoryInterfaces\AuditLogRepositoryInterface;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;

class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function findById(string $id): ?AuditLog
    {
        $model = AuditLogModel::withoutGlobalScopes()->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByAuditable(string $tenantId, string $type, string $id): array
    {
        return AuditLogModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('auditable_type', $type)
            ->where('auditable_id', $id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(AuditLogModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByTenant(string $tenantId, array $filters = []): array
    {
        $query = AuditLogModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId);

        if (isset($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['auditable_type'])) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        if (isset($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->orderByDesc('created_at')
            ->get()
            ->map(fn(AuditLogModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(AuditLog $log): void
    {
        $model = new AuditLogModel();
        $model->id = $log->id;
        $model->fill([
            'tenant_id'      => $log->tenantId,
            'user_id'        => $log->userId,
            'event'          => $log->event,
            'auditable_type' => $log->auditableType,
            'auditable_id'   => $log->auditableId,
            'old_values'     => $log->oldValues,
            'new_values'     => $log->newValues,
            'url'            => $log->url,
            'ip_address'     => $log->ipAddress,
            'user_agent'     => $log->userAgent,
            'tags'           => $log->tags,
            'created_at'     => $log->createdAt,
        ]);
        $model->save();
    }

    private function mapToEntity(AuditLogModel $model): AuditLog
    {
        return new AuditLog(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            userId: $model->user_id !== null ? (string) $model->user_id : null,
            event: (string) $model->event,
            auditableType: (string) $model->auditable_type,
            auditableId: (string) $model->auditable_id,
            oldValues: $model->old_values !== null ? (array) $model->old_values : null,
            newValues: $model->new_values !== null ? (array) $model->new_values : null,
            url: $model->url !== null ? (string) $model->url : null,
            ipAddress: $model->ip_address !== null ? (string) $model->ip_address : null,
            userAgent: $model->user_agent !== null ? (string) $model->user_agent : null,
            tags: $model->tags !== null ? (array) $model->tags : null,
            createdAt: new DateTimeImmutable((string) $model->created_at),
        );
    }
}
