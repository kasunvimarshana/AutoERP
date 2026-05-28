<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Repositories;

use Modules\Core\Application\Contracts\RepositoryPortInterface;
use Modules\Core\Application\DTO\DataRecord;

interface UomConversionRepositoryInterface extends RepositoryPortInterface
{
    public function findConversionBetween(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
        ?int $itemId,
    ): ?DataRecord;

    /** @return list<DataRecord> */
    public function findActiveConversionsForUom(int|string $uomId, int $tenantId): array;
}
