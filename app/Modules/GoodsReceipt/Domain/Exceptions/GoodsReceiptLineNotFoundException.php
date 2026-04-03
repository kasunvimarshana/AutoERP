<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\Exceptions;

class GoodsReceiptLineNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("GoodsReceiptLine [{$id}] not found.");
    }
}
