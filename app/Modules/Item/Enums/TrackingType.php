<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Item\Enums;

enum TrackingType: string
{
    case None = 'none';
    case Batch = 'batch';
    case Lot = 'lot';
    case Serial = 'serial';
}
