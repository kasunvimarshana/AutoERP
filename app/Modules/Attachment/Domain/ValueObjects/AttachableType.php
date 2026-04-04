<?php

namespace Modules\Attachment\Domain\ValueObjects;

final class AttachableType
{
    public const PURCHASE_ORDER = 'purchase_order';
    public const GOODS_RECEIPT  = 'goods_receipt';
    public const SALES_ORDER    = 'sales_order';
    public const STOCK_RETURN   = 'stock_return';
    public const PRODUCT        = 'product';
    public const SUPPLIER       = 'supplier';
    public const CUSTOMER       = 'customer';
    public const DISPATCH       = 'dispatch';

    private function __construct() {}
}
