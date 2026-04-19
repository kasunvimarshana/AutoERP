<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitUser;

interface FindOrganizationUnitUserServiceInterface
{
    public function find(int $id): ?OrganizationUnitUser;

    public function list(array $filters, int $perPage, int $page, ?string $sort = null): LengthAwarePaginator;
}
