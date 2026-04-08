<?php

declare(strict_types=1);

namespace Modules\Order\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface SalesOrderServiceInterface extends ServiceInterface
{
    public function createSalesOrder(array $data): mixed;
    public function confirmOrder(string $id): mixed;
    public function cancelOrder(string $id): mixed;
}
