<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

final class SupplierNotFoundException extends NotFoundException
{
    public function __construct(mixed $id)
    {
        parent::__construct('Supplier', $id);
    }
}
