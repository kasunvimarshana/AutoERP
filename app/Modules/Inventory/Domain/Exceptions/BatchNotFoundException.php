<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class BatchNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('Batch', $id);
    }
}
