<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class SupplierNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('Supplier', $id);
    }
}
