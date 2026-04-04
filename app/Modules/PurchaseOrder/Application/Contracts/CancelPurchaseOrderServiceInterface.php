<?php
namespace Modules\PurchaseOrder\Application\Contracts;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
interface CancelPurchaseOrderServiceInterface
{
    public function execute(int $poId): PurchaseOrder;
}
