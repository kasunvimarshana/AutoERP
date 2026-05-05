<?php

declare(strict_types=1);

namespace Modules\Service\Domain\RepositoryInterfaces;

use Modules\Service\Domain\Entities\ServiceLaborEntry;

interface ServiceLaborEntryRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?ServiceLaborEntry;

    /** @return ServiceLaborEntry[] */
    public function findByWorkOrder(int $tenantId, int $workOrderId): array;

    /** @return ServiceLaborEntry[] */
    public function findByEmployee(int $tenantId, int $employeeId, array $filters = []): array;

    public function save(ServiceLaborEntry $entry): ServiceLaborEntry;

    public function delete(int $tenantId, int $id): bool;
}
