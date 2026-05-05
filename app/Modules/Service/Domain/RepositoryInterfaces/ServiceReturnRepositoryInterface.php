<?php

declare(strict_types=1);

namespace Modules\Service\Domain\RepositoryInterfaces;

use Modules\Service\Domain\Entities\ServiceReturn;

interface ServiceReturnRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?ServiceReturn;

    /** @return ServiceReturn[] */
    public function findByWorkOrder(int $tenantId, int $workOrderId): array;

    public function save(ServiceReturn $return): ServiceReturn;

    public function delete(int $tenantId, int $id): bool;
}
