<?php

declare(strict_types=1);

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
