<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class PurchaseReturnNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('PurchaseReturn', $id);
    }
}
