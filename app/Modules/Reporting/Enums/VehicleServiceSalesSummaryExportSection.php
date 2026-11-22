<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Reporting\Enums;

enum VehicleServiceSalesSummaryExportSection: string
{
    case Full = 'full';
    case Stock = 'stock';
    case Combo = 'combo';
}
