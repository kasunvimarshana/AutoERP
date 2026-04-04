<?php
namespace Modules\GoodsReceipt\Application\Contracts;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
interface PutAwayGoodsReceiptServiceInterface
{
    public function execute(int $grId, int $putAwayBy): GoodsReceipt;
}
