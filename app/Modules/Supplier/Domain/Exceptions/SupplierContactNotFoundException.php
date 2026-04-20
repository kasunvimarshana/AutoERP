<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class SupplierContactNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('SupplierContact', $id);
    }
}
