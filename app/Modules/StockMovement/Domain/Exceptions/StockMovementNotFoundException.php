<?php

declare(strict_types=1);

namespace Modules\StockMovement\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class StockMovementNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('StockMovement', $id);
    }
}
