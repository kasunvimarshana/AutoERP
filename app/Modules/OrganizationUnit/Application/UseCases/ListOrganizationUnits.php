<?php

namespace Modules\OrganizationUnit\Application\UseCases;

use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
