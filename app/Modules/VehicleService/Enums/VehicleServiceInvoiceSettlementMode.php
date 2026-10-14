<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceInvoiceSettlementMode: string
{
    case Credit = 'credit';
    case Direct = 'direct';
}
