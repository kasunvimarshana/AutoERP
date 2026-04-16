<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\FindPermissionServiceInterface;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;

/**
 * Read-only service for querying permissions.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindPermissionService extends BaseService implements FindPermissionServiceInterface
{
    public function __construct(private readonly PermissionRepositoryInterface $permissionRepository)
    {
        parent::__construct($permissionRepository);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
