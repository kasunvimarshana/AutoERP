<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface ConfigurationRepositoryInterface extends RepositoryPortInterface
{
    public function findByKey(string $key): ?DataRecord;

    public function pageByFilters(?string $prefix, ?string $source, int $perPage, int $page): PagedResult;

    public function deleteByKey(string $key): bool;
}
