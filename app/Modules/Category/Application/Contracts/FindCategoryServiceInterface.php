<?php

declare(strict_types=1);

namespace Modules\Category\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Category\Domain\Entities\Category;

/**
 * Contract for hierarchical and flat category read queries.
 *
 * Exposes tree, roots, and find operations through the service layer so that
 * controllers do not inject the repository directly (DIP compliance).
 */
interface FindCategoryServiceInterface
{
    public function find(int $id): ?Category;

    public function getTree(int $tenantId, ?int $rootId = null): Collection;

    public function findRoots(int $tenantId): Collection;
}
