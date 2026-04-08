<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class LocationNotFoundException extends NotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct('WarehouseLocation', $id);
    }
}
