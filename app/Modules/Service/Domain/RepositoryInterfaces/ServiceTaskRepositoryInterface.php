<?php

declare(strict_types=1);

namespace Modules\Service\Domain\RepositoryInterfaces;

use Modules\Service\Domain\Entities\ServiceTask;

interface ServiceTaskRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?ServiceTask;

    /** @return ServiceTask[] */
    public function findByWorkOrder(int $tenantId, int $workOrderId): array;

    /** @return ServiceTask[] */
    public function findByTenant(int $tenantId, ?int $orgUnitId = null, array $filters = []): array;

    public function nextLineNumber(int $tenantId, int $workOrderId): int;

    public function save(ServiceTask $task): ServiceTask;

    public function delete(int $tenantId, int $id): bool;
}
