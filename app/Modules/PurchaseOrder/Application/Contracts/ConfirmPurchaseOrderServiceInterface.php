<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Application\Contracts;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
interface ConfirmPurchaseOrderServiceInterface {
    public function execute(int $id): PurchaseOrder;
}
