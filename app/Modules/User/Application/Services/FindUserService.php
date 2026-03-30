<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

/**
 * Read-only service for querying users.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindUserService extends BaseService implements FindUserServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
        parent::__construct($userRepository);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
