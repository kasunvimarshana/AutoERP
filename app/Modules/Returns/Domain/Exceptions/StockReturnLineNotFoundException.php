<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class StockReturnLineNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('StockReturnLine', $id);
    }
}
