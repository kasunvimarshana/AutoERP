<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class PurchaseOrderNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('PurchaseOrder', $id);
    }
}
