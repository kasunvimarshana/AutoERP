<?php
namespace Modules\GoodsReceipt\Application\Contracts;
use Modules\GoodsReceipt\Application\DTOs\GoodsReceiptData;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
interface CreateGoodsReceiptServiceInterface
{
    public function execute(GoodsReceiptData $data): GoodsReceipt;
}
