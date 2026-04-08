<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

final class LocationNotFoundException extends NotFoundException
{
    public function __construct(mixed $id)
    {
        parent::__construct('Location', $id);
    }
}
