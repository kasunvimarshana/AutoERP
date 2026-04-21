<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class SalesReturnNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct('SalesReturn', $id);
    }
}
