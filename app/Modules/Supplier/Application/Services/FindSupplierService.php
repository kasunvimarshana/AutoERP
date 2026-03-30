<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\FindSupplierServiceInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

/**
 * Read-only service for querying suppliers.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindSupplierService extends BaseService implements FindSupplierServiceInterface
{
    public function __construct(private readonly SupplierRepositoryInterface $supplierRepository)
    {
        parent::__construct($supplierRepository);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
