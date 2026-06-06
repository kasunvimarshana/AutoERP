<?php

declare(strict_types=1);

namespace Modules\Item\Enums;

enum TrackingType: string
{
    case None = 'none';
    case Batch = 'batch';
    case Lot = 'lot';
    case Serial = 'serial';
}
