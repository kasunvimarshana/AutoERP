<?php

declare(strict_types=1);

namespace Modules\Item\Application\Repositories;

use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface ItemTypeRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function pageForTenant(int $tenantId, array $criteria, int $perPage, int $page): PagedResult;
}
