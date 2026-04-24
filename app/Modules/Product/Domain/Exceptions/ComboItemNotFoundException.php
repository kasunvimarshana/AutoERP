<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class ComboItemNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('ComboItem', $id);
    }
}
