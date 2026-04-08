<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Services;

use Illuminate\Support\Str;
use Modules\Audit\Application\Contracts\AuditLogServiceInterface;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\RepositoryInterfaces\AuditLogRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;

class AuditLogService implements AuditLogServiceInterface
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    public function log(
        string $tenantId,
        string $event,
        string $auditableType,
        string $auditableId,
        array $oldValues = [],
        array $newValues = [],
        array $context = [],
    ): AuditLog {
        $entry = new AuditLog(
            id: (string) Str::uuid(),
            tenantId: $tenantId,
            userId: $context['user_id'] ?? null,
            event: $event,
            auditableType: $auditableType,
            auditableId: $auditableId,
            oldValues: $oldValues !== [] ? $oldValues : null,
            newValues: $newValues !== [] ? $newValues : null,
            url: $context['url'] ?? null,
            ipAddress: $context['ip_address'] ?? null,
            userAgent: $context['user_agent'] ?? null,
            tags: isset($context['tags']) && $context['tags'] !== [] ? $context['tags'] : null,
            createdAt: now(),
        );

        $this->auditLogRepository->save($entry);

        return $entry;
    }

    public function getForEntity(string $tenantId, string $type, string $id): array
    {
        return $this->auditLogRepository->findByAuditable($tenantId, $type, $id);
    }

    public function getForTenant(string $tenantId, array $filters = []): array
    {
        return $this->auditLogRepository->findByTenant($tenantId, $filters);
    }

    public function getById(string $id): AuditLog
    {
        $log = $this->auditLogRepository->findById($id);

        if ($log === null) {
            throw new NotFoundException("AuditLog [{$id}] not found.");
        }

        return $log;
    }
}
