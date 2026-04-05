<?php
declare(strict_types=1);
namespace Modules\Audit\Application\Services;

use Modules\Audit\Application\Contracts\QueryAuditLogServiceInterface;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\Exceptions\AuditLogNotFoundException;
use Modules\Audit\Domain\RepositoryInterfaces\AuditLogRepositoryInterface;

class QueryAuditLogService implements QueryAuditLogServiceInterface
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
    ) {}

    public function findById(int $id): AuditLog
    {
        $log = $this->repository->findById($id);
        if ($log === null) {
            throw new AuditLogNotFoundException($id);
        }
        return $log;
    }

    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 50, int $page = 1): array
    {
        return $this->repository->findByTenant($tenantId, $filters, $perPage, $page);
    }

    public function findByEntity(int $tenantId, string $entityType, string $entityId): array
    {
        return $this->repository->findByEntity($tenantId, $entityType, $entityId);
    }

    public function purgeOlderThan(int $days): int
    {
        $before = new \DateTimeImmutable("-{$days} days");
        return $this->repository->deleteOlderThan($before);
    }
}
