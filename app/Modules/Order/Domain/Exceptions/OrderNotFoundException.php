<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

final class OrderNotFoundException extends NotFoundException
{
    public function __construct(mixed $id)
    {
        parent::__construct('Order', $id);
    }
}
