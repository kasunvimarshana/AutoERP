<?php

declare(strict_types=1);

namespace Modules\Service\Domain\RepositoryInterfaces;

use Modules\Service\Domain\Entities\ServicePart;

interface ServicePartRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?ServicePart;

    /** @return ServicePart[] */
    public function findByWorkOrder(int $tenantId, int $workOrderId): array;

    public function save(ServicePart $part): ServicePart;

    public function delete(int $tenantId, int $id): bool;
}
