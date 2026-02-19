<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class QuotationNotFoundException extends NotFoundException
{
    protected $message = 'Quotation not found';

    protected $code = 'QUOTATION_NOT_FOUND';
}
