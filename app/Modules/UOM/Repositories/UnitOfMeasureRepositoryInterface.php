<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\UOM\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

interface UnitOfMeasureRepositoryInterface extends RepositoryPortInterface
{
    public function findByIdInTenant(int|string $id, int $tenantId): ?DataRecord;

    public function findByCode(string $code, int $tenantId): ?DataRecord;

    public function findBySymbol(string $symbol, int $tenantId): ?DataRecord;

    public function findBaseUomForType(string $type, int $tenantId): ?DataRecord;

    public function searchByNameOrSymbol(string $search, int $tenantId, int $perPage, int $page): PagedResult;
}
