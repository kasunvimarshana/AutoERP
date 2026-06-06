<?php

declare(strict_types=1);

namespace Modules\Sequence\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface SequenceRepositoryInterface extends RepositoryPortInterface
{
    public function findByScope(
        int $tenantId,
        ?int $organizationUnitId,
        string $documentType,
        ?string $periodValue,
    ): ?DataRecord;

    public function pageByFilters(
        ?int $tenantId,
        ?int $organizationUnitId,
        ?string $documentType,
        ?string $periodType,
        ?string $periodValue,
        int $perPage,
        int $page,
    ): PagedResult;

    public function findByScopeForUpdate(
        int $tenantId,
        ?int $organizationUnitId,
        string $documentType,
        ?string $periodValue,
    ): ?DataRecord;

    public function updateNextNumberWithVersion(
        int|string $id,
        int $expectedRowVersion,
        int $nextNumber,
    ): ?DataRecord;
}
