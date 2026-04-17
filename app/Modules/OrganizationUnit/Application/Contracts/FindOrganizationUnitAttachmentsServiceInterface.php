<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;

interface FindOrganizationUnitAttachmentsServiceInterface
{
    public function find(int $id): ?OrganizationUnitAttachment;

    public function findByUuid(string $uuid): ?OrganizationUnitAttachment;

    public function getByOrganizationUnit(int $organizationUnitId, ?string $type = null): Collection;

    public function paginateByOrganizationUnit(int $organizationUnitId, ?string $type, int $perPage, int $page): LengthAwarePaginator;
}
