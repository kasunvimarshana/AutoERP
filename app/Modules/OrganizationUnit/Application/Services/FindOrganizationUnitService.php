<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

/**
 * Read-only service for querying organization units.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindOrganizationUnitService extends BaseService implements FindOrganizationUnitServiceInterface
{
    public function __construct(private readonly OrganizationUnitRepositoryInterface $orgUnitRepository)
    {
        parent::__construct($orgUnitRepository);
    }

    /**
     * Return the hierarchical tree of organization units for a given tenant.
     *
     * @return array<int|string, mixed>
     */
    public function getTree(int $tenantId, ?int $rootId = null): array
    {
        return $this->orgUnitRepository->getTree($tenantId, $rootId);
    }

    /**
     * Return all descendants of the given unit (depth-first, ordered by _lft).
     *
     * @return array<int, \Modules\OrganizationUnit\Domain\Entities\OrganizationUnit>
     */
    public function getDescendants(int $id): array
    {
        return $this->orgUnitRepository->getDescendants($id);
    }

    /**
     * Return the ancestor chain of the given unit (root → direct parent order).
     *
     * @return array<int, \Modules\OrganizationUnit\Domain\Entities\OrganizationUnit>
     */
    public function getAncestors(int $id): array
    {
        return $this->orgUnitRepository->getAncestors($id);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
