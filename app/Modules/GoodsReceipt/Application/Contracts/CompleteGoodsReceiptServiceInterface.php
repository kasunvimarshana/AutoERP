<?php
namespace Modules\GoodsReceipt\Application\Contracts;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
interface CompleteGoodsReceiptServiceInterface
{
    public function execute(int $grId): GoodsReceipt;
}
