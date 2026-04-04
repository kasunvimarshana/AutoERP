<?php
namespace Modules\PurchaseOrder\Application\Contracts;
use Modules\PurchaseOrder\Application\DTOs\PurchaseOrderData;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
interface CreatePurchaseOrderServiceInterface
{
    public function execute(PurchaseOrderData $data): PurchaseOrder;
}
