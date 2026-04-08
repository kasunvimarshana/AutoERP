<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class PurchaseOrderLineNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('PurchaseOrderLine', $id);
    }
}
