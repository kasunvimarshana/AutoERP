<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface TenantRepositoryInterface extends RepositoryPortInterface
{
    public function findByCode(string $code): ?DataRecord;

    public function findByUuid(string $uuid): ?DataRecord;

    public function findByIsolationKey(string $isolationKey): ?DataRecord;

    public function pageByFilters(?string $status, ?bool $isActive, ?string $search, int $perPage, int $page): PagedResult;
}
