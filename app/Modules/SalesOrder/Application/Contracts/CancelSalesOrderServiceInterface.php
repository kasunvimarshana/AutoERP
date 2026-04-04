<?php

namespace Modules\SalesOrder\Application\Contracts;

use Modules\SalesOrder\Domain\Entities\SalesOrder;

interface CancelSalesOrderServiceInterface
{
    public function execute(SalesOrder $so): SalesOrder;
}
