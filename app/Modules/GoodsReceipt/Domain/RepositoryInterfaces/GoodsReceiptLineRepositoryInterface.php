<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceiptLine;

interface GoodsReceiptLineRepositoryInterface extends RepositoryInterface
{
    public function save(GoodsReceiptLine $line): GoodsReceiptLine;
    public function findById(int $id): ?GoodsReceiptLine;
    public function delete(mixed $id): bool;
    public function list(array $filters = [], ?int $perPage = null, int $page = 1): mixed;
    public function findByGoodsReceipt(int $goodsReceiptId): Collection;
}
