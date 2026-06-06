<?php

declare(strict_types=1);

namespace Modules\Item\Enums;

enum ItemCodeType: string
{
    case Sku = 'sku';
    case Barcode = 'barcode';
    case SupplierCode = 'supplier_code';
    case CustomerCode = 'customer_code';
    case InternalCode = 'internal_code';
    case OemCode = 'oem_code';
}
