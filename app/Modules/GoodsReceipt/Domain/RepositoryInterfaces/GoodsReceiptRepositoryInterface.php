<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Domain\RepositoryInterfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
interface GoodsReceiptRepositoryInterface {
    public function findById(int $id): ?GoodsReceipt;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data, array $lines): GoodsReceipt;
    public function update(int $id, array $data): ?GoodsReceipt;
    public function delete(int $id): bool;
}
