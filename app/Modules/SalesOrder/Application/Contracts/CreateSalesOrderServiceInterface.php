<?php

namespace Modules\SalesOrder\Application\Contracts;

use Modules\SalesOrder\Application\DTOs\SalesOrderData;
use Modules\SalesOrder\Domain\Entities\SalesOrder;

interface CreateSalesOrderServiceInterface
{
    public function execute(SalesOrderData $data): SalesOrder;
}
