<?php

declare(strict_types=1);

namespace Modules\Wms\Domain\Contracts;

use Modules\Wms\Domain\Entities\Aisle;

interface AisleRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Aisle;

    public function findByZone(int $tenantId, int $zoneId): array;

    public function save(Aisle $aisle): Aisle;

    public function delete(int $id, int $tenantId): void;
}
