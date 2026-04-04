<?php
namespace Modules\GoodsReceipt\Application\Contracts;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
interface InspectGoodsReceiptServiceInterface
{
    public function execute(int $grId, int $inspectedBy): GoodsReceipt;
}
