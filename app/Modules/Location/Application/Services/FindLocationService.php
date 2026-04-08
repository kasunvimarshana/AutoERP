<?php

declare(strict_types=1);

namespace Modules\Location\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Location\Application\Contracts\FindLocationServiceInterface;
use Modules\Location\Domain\RepositoryInterfaces\LocationRepositoryInterface;

/**
 * Read-only service for querying locations.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindLocationService extends BaseService implements FindLocationServiceInterface
{
    public function __construct(private readonly LocationRepositoryInterface $locationRepository)
    {
        parent::__construct($locationRepository);
    }

    /**
     * Return the hierarchical tree of locations for a given tenant.
     *
     * @return array<int|string, mixed>
     */
    public function getTree(int $tenantId, ?int $rootId = null): array
    {
        return $this->locationRepository->getTree($tenantId, $rootId);
    }

    /**
     * Return all descendants of the given location (depth-first, ordered by _lft).
     *
     * @return array<int, \Modules\Location\Domain\Entities\Location>
     */
    public function getDescendants(int $id): array
    {
        return $this->locationRepository->getDescendants($id);
    }

    /**
     * Return the ancestor chain of the given location (root → direct parent order).
     *
     * @return array<int, \Modules\Location\Domain\Entities\Location>
     */
    public function getAncestors(int $id): array
    {
        return $this->locationRepository->getAncestors($id);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
