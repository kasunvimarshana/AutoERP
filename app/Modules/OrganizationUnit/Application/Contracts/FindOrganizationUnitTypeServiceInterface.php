<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitType;

interface FindOrganizationUnitTypeServiceInterface
{
    public function find(int $id): ?OrganizationUnitType;

    public function list(array $filters, int $perPage, int $page, ?string $sort = null): LengthAwarePaginator;
}
