<?php

namespace Modules\SalesOrder\Application\Contracts;

use Modules\SalesOrder\Domain\Entities\SalesOrder;

interface StartPackingSalesOrderServiceInterface
{
    public function execute(SalesOrder $so, int $packedBy): SalesOrder;
}
