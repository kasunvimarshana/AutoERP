<?php

namespace Modules\Recruitment\Domain\Enums;

enum PositionStatus: string
{
    case Open    = 'open';
    case OnHold  = 'on_hold';
    case Closed  = 'closed';
}
