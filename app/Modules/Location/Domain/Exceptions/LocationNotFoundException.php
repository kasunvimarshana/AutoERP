<?php

declare(strict_types=1);

namespace Modules\Location\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class LocationNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('Location', $id);
    }
}
