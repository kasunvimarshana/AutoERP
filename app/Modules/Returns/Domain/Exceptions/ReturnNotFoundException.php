<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

final class ReturnNotFoundException extends NotFoundException
{
    public function __construct(mixed $id)
    {
        parent::__construct('Return', $id);
    }
}
