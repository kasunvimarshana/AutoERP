<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Audit\Application\Contracts\AuditServiceInterface;
use Modules\Audit\Application\DTOs\AuditLogData;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\RepositoryInterfaces\AuditRepositoryInterface;
use Modules\Audit\Domain\ValueObjects\AuditAction;

class AuditService implements AuditServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'user_id', 'event', 'auditable_type', 'auditable_id'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['occurred_at', 'id', 'tenant_id', 'user_id', 'event'];

    public function __construct(
        private readonly AuditRepositoryInterface $auditRepository,
    ) {}

    public function record(array $data): AuditLog
    {
        $dto = AuditLogData::fromArray($data);

        $log = new AuditLog(
            id: null,
            tenantId: $dto->tenant_id,
            userId: $dto->user_id,
            event: AuditAction::fromDatabase($dto->event),
            auditableType: $dto->auditable_type,
            auditableId: $dto->auditable_id,
            oldValues: $dto->old_values,
            newValues: $dto->new_values,
            url: $dto->url,
            ipAddress: $dto->ip_address,
            userAgent: $dto->user_agent,
            tags: $dto->tags,
            metadata: $dto->metadata,
            occurredAt: new \DateTimeImmutable,
        );

        return $this->auditRepository->record($log);
    }

    public function find(int $id): ?AuditLog
    {
        return $this->auditRepository->find($id);
    }

    public function list(array $filters, int $perPage = 15, int $page = 1, ?string $sort = null): LengthAwarePaginator
    {
        $normalizedFilters = [];

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $normalizedFilters[$field] = $value;
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);

        return $this->auditRepository->list(
            filters: $normalizedFilters,
            perPage: $perPage,
            page: $page,
            sortField: $sortField,
            sortDirection: $sortDirection,
        );
    }

    public function forAuditable(string $auditableType, int|string $auditableId): Collection
    {
        return $this->auditRepository->forAuditable($auditableType, $auditableId);
    }

    public function forAuditablePaginated(
        string $auditableType,
        int|string $auditableId,
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->auditRepository->forAuditablePaginated($auditableType, $auditableId, $perPage, $page);
    }

    public function forTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->auditRepository->forTenant($tenantId, $perPage, $page);
    }

    public function forUser(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->auditRepository->forUser($userId, $perPage, $page);
    }

    public function forEvent(string $event, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->auditRepository->forEvent($event, $perPage, $page);
    }

    public function pruneOlderThan(\DateTimeInterface $before): int
    {
        return $this->auditRepository->pruneOlderThan($before);
    }

    /**
     * @return array{0: string|null, 1: 'asc'|'desc'}
     */
    private function parseSort(?string $sort): array
    {
        if ($sort === null || trim($sort) === '') {
            return ['occurred_at', 'desc'];
        }

        $normalizedSort = trim($sort);
        $direction = str_starts_with($normalizedSort, '-') ? 'desc' : 'asc';
        $field = ltrim($normalizedSort, '-');

        if (! in_array($field, self::ALLOWED_SORTS, true)) {
            return ['occurred_at', 'desc'];
        }

        return [$field, $direction];
    }
}
