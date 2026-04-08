<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class InvalidOrderStatusException extends DomainException
{
    public function __construct(string $current, string $target)
    {
        parent::__construct("Cannot transition order from '{$current}' to '{$target}'.", 422);
    }
}
