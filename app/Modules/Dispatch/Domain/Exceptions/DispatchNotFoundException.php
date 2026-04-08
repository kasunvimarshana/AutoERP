<?php

declare(strict_types=1);

namespace Modules\Dispatch\Domain\Exceptions;

class DispatchNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Dispatch [{$id}] not found.");
    }
}
