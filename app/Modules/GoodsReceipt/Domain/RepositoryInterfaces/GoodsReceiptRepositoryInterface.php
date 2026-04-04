<?php
namespace Modules\GoodsReceipt\Domain\RepositoryInterfaces;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
interface GoodsReceiptRepositoryInterface
{
    public function findById(int $id): ?GoodsReceipt;
    public function findByGrNumber(int $tenantId, string $grNumber): ?GoodsReceipt;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator;
    public function create(array $data): GoodsReceipt;
    public function update(GoodsReceipt $gr, array $data): GoodsReceipt;
    public function save(GoodsReceipt $gr): GoodsReceipt;
}
