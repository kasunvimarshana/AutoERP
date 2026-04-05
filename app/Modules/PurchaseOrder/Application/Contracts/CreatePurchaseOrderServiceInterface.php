<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Application\Contracts;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
interface CreatePurchaseOrderServiceInterface {
    public function execute(array $data, array $lines): PurchaseOrder;
}
