<?php

declare(strict_types=1);

namespace Modules\Account\Application\Services;

use Modules\Account\Application\Contracts\FindAccountServiceInterface;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

/**
 * Read-only service for querying accounts.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindAccountService extends BaseService implements FindAccountServiceInterface
{
    public function __construct(private readonly AccountRepositoryInterface $accountRepository)
    {
        parent::__construct($accountRepository);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
