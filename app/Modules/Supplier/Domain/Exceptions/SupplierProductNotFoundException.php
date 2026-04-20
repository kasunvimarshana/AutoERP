<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class SupplierProductNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('SupplierProduct', $id);
    }
}
