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

enum ItemBaseUomRevisionStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Applied = 'applied';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
}
