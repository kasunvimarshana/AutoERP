<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Application\Contracts;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
interface PutAwayGoodsReceiptServiceInterface { public function execute(int $id, int $putAwayBy): GoodsReceipt; }
