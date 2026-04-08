<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class SalesOrderLineNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('SalesOrderLine', $id);
    }
}
