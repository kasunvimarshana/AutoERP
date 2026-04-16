<?php

declare(strict_types=1);

namespace Modules\Core\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\AuditServiceInterface;
use Modules\Core\Application\DTOs\AuditLogData;
use Modules\Core\Domain\Entities\AuditLog;
use Modules\Core\Domain\RepositoryInterfaces\AuditRepositoryInterface;
use Modules\Core\Domain\ValueObjects\AuditAction;

/**
 * Application service that orchestrates audit log creation and retrieval.
 *
 * It deliberately does NOT extend BaseService because audit recording must
 * never be wrapped in a business-domain transaction – it should succeed
 * independently (or fail silently) so that auditing does not interfere with
 * the primary operation.
 */
class AuditService implements AuditServiceInterface
{
    public function __construct(
        private readonly AuditRepositoryInterface $auditRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
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
            createdAt: new \DateTimeImmutable,
        );

        return $this->auditRepository->record($log);
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?AuditLog
    {
        return $this->auditRepository->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function forAuditable(string $auditableType, int|string $auditableId): Collection
    {
        return $this->auditRepository->forAuditable($auditableType, $auditableId);
    }

    /**
     * {@inheritdoc}
     */
    public function forAuditablePaginated(
        string $auditableType,
        int|string $auditableId,
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->auditRepository->forAuditablePaginated($auditableType, $auditableId, $perPage, $page);
    }

    /**
     * {@inheritdoc}
     */
    public function forTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->auditRepository->forTenant($tenantId, $perPage, $page);
    }

    /**
     * {@inheritdoc}
     */
    public function forUser(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->auditRepository->forUser($userId, $perPage, $page);
    }

    /**
     * {@inheritdoc}
     */
    public function pruneOlderThan(\DateTimeInterface $before): int
    {
        return $this->auditRepository->pruneOlderThan($before);
    }
}
