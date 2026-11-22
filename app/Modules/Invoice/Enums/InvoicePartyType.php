<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Enums;

enum InvoicePartyType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';

    public static function forDirection(InvoiceDirection $direction): self
    {
        return match ($direction) {
            InvoiceDirection::Outbound => self::Customer,
            InvoiceDirection::Inbound => self::Supplier,
        };
    }
}
