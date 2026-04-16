<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\FindRoleServiceInterface;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;

/**
 * Read-only service for querying roles.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindRoleService extends BaseService implements FindRoleServiceInterface
{
    public function __construct(private readonly RoleRepositoryInterface $roleRepository)
    {
        parent::__construct($roleRepository);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
