<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Application\Contracts;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
interface InspectGoodsReceiptServiceInterface { public function execute(int $id, int $inspectedBy): GoodsReceipt; }
