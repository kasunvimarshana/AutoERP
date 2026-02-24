<?php

namespace Modules\AssetManagement\Domain\Enums;

enum DepreciationMethod: string
{
    case StraightLine      = 'straight_line';
    case DecliningBalance  = 'declining_balance';
}
