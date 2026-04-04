<?php
namespace Modules\PurchaseOrder\Application\Contracts;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
interface ApprovePurchaseOrderServiceInterface
{
    public function execute(int $poId, int $approvedBy): PurchaseOrder;
}
