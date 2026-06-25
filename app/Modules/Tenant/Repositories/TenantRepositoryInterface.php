<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Tenant\Data\TenantDirectoryFilters;

interface TenantRepositoryInterface
{
    public function findById(int|string $id): ?DataRecord;
    public function findByCode(string $code): ?DataRecord;
    public function findByUuid(string $uuid): ?DataRecord;
    public function findBySlug(string $slug): ?DataRecord;
    public function lockById(int|string $id): ?DataRecord;
    public function create(array $attributes): DataRecord;
    public function updateWithVersion(int|string $id, int $expectedVersion, array $attributes): ?DataRecord;
    public function pageByFilters(TenantDirectoryFilters $filters): PagedResult;
}
