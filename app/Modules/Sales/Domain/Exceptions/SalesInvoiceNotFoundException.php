<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class SalesInvoiceNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct('SalesInvoice', $id);
    }
}
