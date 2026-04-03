<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class UomCategoryNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('UomCategory', $id);
    }
}
