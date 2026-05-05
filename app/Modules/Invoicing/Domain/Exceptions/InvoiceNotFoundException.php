<?php

declare(strict_types=1);

namespace Modules\Invoicing\Domain\Exceptions;

use RuntimeException;

class InvoiceNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Invoice [{$id}] not found.");
    }
}
