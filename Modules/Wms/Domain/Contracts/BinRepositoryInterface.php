<?php

declare(strict_types=1);

namespace Modules\Wms\Domain\Contracts;

use Modules\Wms\Domain\Entities\Bin;

interface BinRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Bin;

    public function findByAisle(int $tenantId, int $aisleId): array;

    public function save(Bin $bin): Bin;

    public function delete(int $id, int $tenantId): void;
}
