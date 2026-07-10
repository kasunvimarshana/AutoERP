<?php

declare(strict_types=1);

namespace Modules\Sequence\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

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

    /** @param array<string, mixed> $attributes */
    public function insertIfMissing(array $attributes): bool;

    public function updateNextNumberWithVersion(
        int|string $id,
        int $expectedRowVersion,
        int $nextNumber,
    ): ?DataRecord;
}
