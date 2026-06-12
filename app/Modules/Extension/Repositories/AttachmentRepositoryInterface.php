<?php

declare(strict_types=1);

namespace Modules\Extension\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Extension\Models\AttachmentModel;

interface AttachmentRepositoryInterface
{
    public function findAccessible(
        int|string $id,
        int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
    ): ?AttachmentModel;

    public function findPublicByUuid(string $uuid): ?AttachmentModel;

    public function paginateAccessible(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
        array $filters,
        int $perPage,
        int $page,
    ): LengthAwarePaginator;

    public function versionsAccessible(
        string $versionGroupUuid,
        int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
    ): array;

    public function create(array $attributes): AttachmentModel;

    public function update(AttachmentModel $attachment, array $attributes): AttachmentModel;

    public function delete(AttachmentModel $attachment): bool;

    public function nextVersionNumber(int $tenantId, string $versionGroupUuid): int;

    public function markVersionGroupNotCurrent(int $tenantId, string $versionGroupUuid, int $userId): void;

    public function transaction(callable $callback): mixed;
}
