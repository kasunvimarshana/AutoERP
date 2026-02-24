<?php

namespace Modules\Inventory\Domain\Enums;

enum LotStatus: string
{
    case Active  = 'active';
    case Blocked = 'blocked';
}
