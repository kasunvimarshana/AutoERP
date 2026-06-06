<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

interface TenantRepositoryInterface extends RepositoryPortInterface
{
    public function findByCode(string $code): ?DataRecord;

    public function findByUuid(string $uuid): ?DataRecord;

    public function findByIsolationKey(string $isolationKey): ?DataRecord;

    public function pageByFilters(?string $status, ?bool $isActive, ?string $search, int $perPage, int $page): PagedResult;
}
