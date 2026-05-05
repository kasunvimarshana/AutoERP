<?php

declare(strict_types=1);

namespace Modules\Receipts\Domain\Exceptions;

use RuntimeException;

class ReceiptNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Receipt [{$id}] not found.");
    }
}
