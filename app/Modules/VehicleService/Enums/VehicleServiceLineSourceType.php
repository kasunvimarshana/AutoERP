<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Enums;

enum VehicleServiceLineSourceType: string
{
    case InventoryItem = 'inventory_item';
    case ExternalItem = 'external_item';
    case ServiceItem = 'service_item';
    case LabourItem = 'labour_item';
    case ComboParent = 'combo_parent';
    case ComboChild = 'combo_child';
}
