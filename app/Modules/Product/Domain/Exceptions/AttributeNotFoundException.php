<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class AttributeNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('Attribute', $id);
    }
}
