<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\RepositoryInterfaces\AuditRepositoryInterface;
use Modules\Audit\Domain\ValueObjects\AuditAction;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;

class EloquentAuditRepository implements AuditRepositoryInterface
{
    public function __construct(private readonly AuditLogModel $model) {}

    public function record(AuditLog $log): AuditLog
    {
        $model = $this->model->create([
            'tenant_id' => $log->getTenantId(),
            'user_id' => $log->getUserId(),
            'event' => $log->getEvent()->value(),
            'auditable_type' => $log->getAuditableType(),
            'auditable_id' => (string) $log->getAuditableId(),
            'old_values' => $log->getOldValues(),
            'new_values' => $log->getNewValues(),
            'url' => $log->getUrl(),
            'ip_address' => $log->getIpAddress(),
            'user_agent' => $log->getUserAgent(),
            'tags' => $log->getTags(),
            'metadata' => $log->getMetadata(),
        ]);

        return $this->mapModelToDomainEntity($model);
    }

    public function find(int $id): ?AuditLog
    {
        $model = $this->model->find($id);

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function list(
        array $filters,
        int $perPage = 15,
        int $page = 1,
        ?string $sortField = 'occurred_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        if ($sortField !== null && $sortField !== '') {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (AuditLogModel $m) => $this->mapModelToDomainEntity($m));
    }

    public function forAuditable(string $auditableType, int|string $auditableId): Collection
    {
        return $this->model
            ->where('auditable_type', $auditableType)
            ->where('auditable_id', (string) $auditableId)
            ->orderByDesc('occurred_at')
            ->get()
            ->map(fn (AuditLogModel $m) => $this->mapModelToDomainEntity($m));
    }

    public function forAuditablePaginated(
        string $auditableType,
        int|string $auditableId,
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->model
            ->where('auditable_type', $auditableType)
            ->where('auditable_id', (string) $auditableId)
            ->orderByDesc('occurred_at')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (AuditLogModel $m) => $this->mapModelToDomainEntity($m));
    }

    public function forTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->orderByDesc('occurred_at')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (AuditLogModel $m) => $this->mapModelToDomainEntity($m));
    }

    public function forUser(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderByDesc('occurred_at')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (AuditLogModel $m) => $this->mapModelToDomainEntity($m));
    }

    public function forEvent(string $event, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model
            ->where('event', $event)
            ->orderByDesc('occurred_at')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (AuditLogModel $m) => $this->mapModelToDomainEntity($m));
    }

    public function pruneOlderThan(\DateTimeInterface $before): int
    {
        return $this->model
            ->withTrashed()
            ->where('occurred_at', '<', $before)
            ->forceDelete();
    }

    private function mapModelToDomainEntity(AuditLogModel $model): AuditLog
    {
        return new AuditLog(
            id: $model->id,
            tenantId: $model->tenant_id,
            userId: $model->user_id,
            event: AuditAction::fromDatabase($model->event),
            auditableType: $model->auditable_type,
            auditableId: $model->auditable_id,
            oldValues: $model->old_values,
            newValues: $model->new_values,
            url: $model->url,
            ipAddress: $model->ip_address,
            userAgent: $model->user_agent,
            tags: $model->tags,
            metadata: $model->metadata,
            occurredAt: $model->occurred_at,
        );
    }
}
