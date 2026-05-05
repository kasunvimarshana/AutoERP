<?php

declare(strict_types=1);

namespace Modules\Service\Domain\RepositoryInterfaces;

use Modules\Service\Domain\Entities\ServiceWarrantyClaim;

interface ServiceWarrantyClaimRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?ServiceWarrantyClaim;

    /** @return ServiceWarrantyClaim[] */
    public function findByWorkOrder(int $tenantId, int $workOrderId): array;

    public function save(ServiceWarrantyClaim $claim): ServiceWarrantyClaim;

    public function delete(int $tenantId, int $id): bool;
}
