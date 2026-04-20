<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Pricing\Domain\Entities\PriceList;

interface PriceListRepositoryInterface extends RepositoryInterface
{
    public function save(PriceList $priceList): PriceList;

    public function findByTenantAndName(int $tenantId, string $name): ?PriceList;

    public function clearDefaultByType(int $tenantId, string $type, ?int $excludeId = null): void;

    public function find(int|string $id, array $columns = ['*']): ?PriceList;
}
