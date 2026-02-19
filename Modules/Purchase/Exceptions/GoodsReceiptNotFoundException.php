<?php

declare(strict_types=1);

namespace Modules\Purchase\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class GoodsReceiptNotFoundException extends NotFoundException
{
    protected $message = 'Goods receipt not found';

    protected $code = 'GOODS_RECEIPT_NOT_FOUND';
}
