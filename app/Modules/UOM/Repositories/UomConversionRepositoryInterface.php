<?php

declare(strict_types=1);

namespace Modules\UOM\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface UomConversionRepositoryInterface extends RepositoryPortInterface
{
    public function findByIdInTenant(int|string $id, int $tenantId): ?DataRecord;

    public function findConversionBetween(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
        ?int $itemId,
    ): ?DataRecord;

    /** @return list<DataRecord> */
    public function findActiveConversionsForUom(int|string $uomId, int $tenantId): array;
}
