<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class SupplierNotFoundException extends NotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct('Supplier', $id);
    }
}
