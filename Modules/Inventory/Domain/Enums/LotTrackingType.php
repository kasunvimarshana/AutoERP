<?php

namespace Modules\Inventory\Domain\Enums;

enum LotTrackingType: string
{
    case None   = 'none';
    case Lot    = 'lot';
    case Serial = 'serial';
}
