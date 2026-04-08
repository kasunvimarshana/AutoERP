<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\Exceptions;

class GoodsReceiptNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("GoodsReceipt [{$id}] not found.");
    }
}
