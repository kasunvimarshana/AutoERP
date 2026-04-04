<?php

namespace Modules\SalesOrder\Application\Contracts;

use Modules\SalesOrder\Domain\Entities\SalesOrder;

interface StartPickingSalesOrderServiceInterface
{
    public function execute(SalesOrder $so, int $pickedBy): SalesOrder;
}
