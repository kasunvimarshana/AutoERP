<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
