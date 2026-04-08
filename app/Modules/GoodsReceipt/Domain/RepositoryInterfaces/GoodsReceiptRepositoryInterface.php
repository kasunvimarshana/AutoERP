<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;

interface GoodsReceiptRepositoryInterface extends RepositoryInterface
{
    public function save(GoodsReceipt $goodsReceipt): GoodsReceipt;
    public function findById(int $id): ?GoodsReceipt;
    public function delete(mixed $id): bool;
    public function list(array $filters = [], ?int $perPage = null, int $page = 1): mixed;
    public function findByPurchaseOrder(int $tenantId, int $poId): Collection;
    public function findBySupplier(int $tenantId, int $supplierId): Collection;
    public function findByStatus(int $tenantId, string $status): Collection;
}
