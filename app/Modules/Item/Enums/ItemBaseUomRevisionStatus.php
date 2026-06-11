<?php

declare(strict_types=1);

namespace Modules\Item\Enums;

enum ItemBaseUomRevisionStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Applied = 'applied';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
}
