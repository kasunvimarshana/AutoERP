<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\RepositoryInterfaces;

use Modules\ServiceCenter\Domain\Entities\ServicePartUsage;

interface ServicePartUsageRepositoryInterface
{
    public function create(ServicePartUsage $part): void;

    public function findById(string $id): ?ServicePartUsage;

    public function getByServiceOrder(string $serviceOrderId): array;

    public function sumCostByServiceOrder(string $serviceOrderId): string;

    public function delete(string $id): void;
}
