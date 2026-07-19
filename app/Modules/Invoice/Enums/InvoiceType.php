<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum InvoiceType: string
{
    case Purchase = 'purchase';
    case Sales = 'sales';
    case Service = 'service';
    case Rental = 'rental';
    case VehicleFinance = 'vehicle_finance';
    case Manual = 'manual';
    case Credit = 'credit';
    case Debit = 'debit';
}
