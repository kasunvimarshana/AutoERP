<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum AllocationMethod: string
{
    case FIFO = 'fifo';
    case FEFO = 'fefo';
    case Batch = 'batch';
    case Serial = 'serial';
    case Manual = 'manual';
}
