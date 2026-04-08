<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class ListOrganizationUnits
{
    /** @var array<string> Allowed filter fields */
    private const ALLOWED_FILTERS = ['tenant_id', 'name', 'code', 'parent_id'];

    public function __construct(
        private OrganizationUnitRepositoryInterface $orgUnitRepo
    ) {}

    public function execute(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $repo = clone $this->orgUnitRepo;
        foreach ($filters as $field => $value) {
            if (in_array($field, self::ALLOWED_FILTERS, true)) {
                $repo->where($field, $value);
            }
        }

        return $repo->paginate($perPage, ['*'], 'page', $page);
    }
}
