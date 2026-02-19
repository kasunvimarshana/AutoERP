<?php

declare(strict_types=1);

namespace Modules\Purchase\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class VendorNotFoundException extends NotFoundException
{
    protected $message = 'Vendor not found';

    protected $code = 'VENDOR_NOT_FOUND';
}
