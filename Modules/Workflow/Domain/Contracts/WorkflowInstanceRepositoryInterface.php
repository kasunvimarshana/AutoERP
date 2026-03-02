<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Contracts;

use Modules\Workflow\Domain\Entities\WorkflowInstance;
use Modules\Workflow\Domain\Entities\WorkflowInstanceLog;

interface WorkflowInstanceRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?WorkflowInstance;

    public function findAll(int $tenantId, int $page, int $perPage): array;

    /** @return WorkflowInstance[] */
    public function findByEntity(string $entityType, int $entityId, int $tenantId): array;

    public function save(WorkflowInstance $entity): WorkflowInstance;

    public function delete(int $id, int $tenantId): void;

    /** @return WorkflowInstanceLog[] */
    public function findLogs(int $instanceId, int $tenantId): array;

    public function saveLog(WorkflowInstanceLog $log): WorkflowInstanceLog;
}
