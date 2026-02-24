<?php

namespace Modules\ProjectManagement\Domain\Enums;

enum ProjectStatus: string
{
    case PLANNING   = 'planning';
    case ACTIVE     = 'active';
    case ON_HOLD    = 'on_hold';
    case COMPLETED  = 'completed';
    case CANCELLED  = 'cancelled';
}
