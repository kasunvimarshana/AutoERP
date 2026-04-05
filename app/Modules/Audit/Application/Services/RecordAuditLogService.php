<?php
declare(strict_types=1);
namespace Modules\Audit\Application\Services;

use Modules\Audit\Application\Contracts\RecordAuditLogServiceInterface;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\RepositoryInterfaces\AuditLogRepositoryInterface;

class RecordAuditLogService implements RecordAuditLogServiceInterface
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
    ) {}

    public function record(
        int $tenantId,
        ?int $userId,
        string $event,
        string $entityType,
        ?string $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $url = null,
    ): AuditLog {
        return $this->repository->create([
            'tenant_id'   => $tenantId,
            'user_id'     => $userId,
            'event'       => $event,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => $ipAddress,
            'user_agent'  => $userAgent,
            'url'         => $url,
        ]);
    }
}
