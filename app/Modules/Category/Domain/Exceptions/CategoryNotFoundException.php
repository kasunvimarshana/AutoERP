<?php

declare(strict_types=1);

namespace Modules\Category\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class CategoryNotFoundException extends NotFoundException
{
    public function __construct(mixed $id)
    {
        parent::__construct('Category', $id);
    }
}
