<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Customer\Application\Contracts\FindCustomerServiceInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

/**
 * Read-only service for querying customers.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindCustomerService extends BaseService implements FindCustomerServiceInterface
{
    public function __construct(private readonly CustomerRepositoryInterface $customerRepository)
    {
        parent::__construct($customerRepository);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
