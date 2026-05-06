<?php

declare(strict_types=1);

namespace Modules\Service\Domain\RepositoryInterfaces;

use Modules\Core\Domain\RepositoryInterfaces\RepositoryInterface;
use Modules\Service\Domain\Entities\ServiceWorkOrder;

interface ServiceWorkOrderRepositoryInterface extends RepositoryInterface
{
    public function findById(int $tenantId, int $id): ?ServiceWorkOrder;

    public function findByTenant(int $tenantId, ?int $orgUnitId = null, array $filters = []): array;

    public function save(ServiceWorkOrder $workOrder): ServiceWorkOrder;

    public function delete(int $tenantId, int $id): bool;

    public function nextJobCardNumber(int $tenantId, ?int $orgUnitId): string;
}
