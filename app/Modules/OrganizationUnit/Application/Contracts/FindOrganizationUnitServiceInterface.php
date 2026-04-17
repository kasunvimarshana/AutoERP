<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;

interface FindOrganizationUnitServiceInterface
{
    public function find(int $id): ?OrganizationUnit;

    public function list(array $filters, int $perPage, int $page, ?string $sort = null, ?string $include = null): LengthAwarePaginator;
}
