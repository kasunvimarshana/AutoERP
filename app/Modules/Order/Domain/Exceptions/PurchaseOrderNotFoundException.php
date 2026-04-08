<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class PurchaseOrderNotFoundException extends NotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct('PurchaseOrder', $id);
    }
}
