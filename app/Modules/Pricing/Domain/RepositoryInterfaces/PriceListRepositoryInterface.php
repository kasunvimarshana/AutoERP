<?php
namespace Modules\Pricing\Domain\RepositoryInterfaces;
use Modules\Pricing\Domain\Entities\PriceList;

interface PriceListRepositoryInterface
{
    public function findById(int $id): ?PriceList;
    public function findByCode(int $tenantId, string $code): ?PriceList;
    public function findDefault(int $tenantId): ?PriceList;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): mixed;
    public function create(array $data): PriceList;
    public function update(PriceList $priceList, array $data): PriceList;
    public function delete(PriceList $priceList): bool;
}
