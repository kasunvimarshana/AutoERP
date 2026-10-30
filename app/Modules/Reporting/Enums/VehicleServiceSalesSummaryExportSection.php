<?php

declare(strict_types=1);

namespace Modules\Reporting\Enums;

enum VehicleServiceSalesSummaryExportSection: string
{
    case Full = 'full';
    case Stock = 'stock';
    case Combo = 'combo';
}
