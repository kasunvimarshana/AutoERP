<?php
namespace Modules\GoodsReceipt\Domain\RepositoryInterfaces;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceiptLine;
interface GoodsReceiptLineRepositoryInterface
{
    public function findById(int $id): ?GoodsReceiptLine;
    public function findByGoodsReceipt(int $goodsReceiptId): array;
    public function create(array $data): GoodsReceiptLine;
    public function update(GoodsReceiptLine $line, array $data): GoodsReceiptLine;
}
