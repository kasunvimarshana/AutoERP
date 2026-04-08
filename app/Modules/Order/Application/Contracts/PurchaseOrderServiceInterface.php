<?php

declare(strict_types=1);

namespace Modules\Order\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface PurchaseOrderServiceInterface extends ServiceInterface
{
    public function createPurchaseOrder(array $data): mixed;
    public function receiveOrder(string $id, array $receipts): mixed;
    public function cancelOrder(string $id): mixed;
}
