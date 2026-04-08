<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Pricing\Domain\Entities\PriceList;

interface PriceListRepositoryInterface extends RepositoryInterface
{
    public function save(PriceList $priceList): PriceList;

    public function findById(int $id): ?PriceList;

    public function findByCode(int $tenantId, string $code): ?PriceList;

    public function findByTenantAndType(int $tenantId, string $type): array;

    public function list(array $filters, int $perPage, int $page): LengthAwarePaginator;
}
