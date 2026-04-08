<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Exceptions;

use RuntimeException;

class ComboItemNotFoundException extends RuntimeException
{
    public function __construct(int|string $id)
    {
        parent::__construct("Combo item with ID [{$id}] not found.");
    }
}
