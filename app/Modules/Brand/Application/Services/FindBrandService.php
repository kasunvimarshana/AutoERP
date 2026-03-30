<?php

declare(strict_types=1);

namespace Modules\Brand\Application\Services;

use Modules\Brand\Application\Contracts\FindBrandServiceInterface;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

/**
 * Read-only service for querying brands.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindBrandService extends BaseService implements FindBrandServiceInterface
{
    public function __construct(private readonly BrandRepositoryInterface $brandRepository)
    {
        parent::__construct($brandRepository);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
