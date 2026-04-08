<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

final class StockItemNotFoundException extends NotFoundException
{
    public function __construct(mixed $id)
    {
        parent::__construct('StockItem', $id);
    }
}
