<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class InvoiceNotFoundException extends NotFoundException
{
    protected $message = 'Invoice not found';

    protected $code = 'INVOICE_NOT_FOUND';
}
