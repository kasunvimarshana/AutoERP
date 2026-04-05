<?php
declare(strict_types=1);
namespace Modules\Pricing\Domain\RepositoryInterfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Pricing\Domain\Entities\PriceList;
interface PriceListRepositoryInterface {
    public function findById(int $id): ?PriceList;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): PriceList;
    public function update(int $id, array $data): ?PriceList;
    public function delete(int $id): bool;
}
