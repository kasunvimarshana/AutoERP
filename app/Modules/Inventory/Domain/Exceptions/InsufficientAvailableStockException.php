<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class InsufficientAvailableStockException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Insufficient available stock for reservation.');
    }
}
