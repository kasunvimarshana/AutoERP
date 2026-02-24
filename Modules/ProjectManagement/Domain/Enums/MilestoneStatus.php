<?php

namespace Modules\ProjectManagement\Domain\Enums;

enum MilestoneStatus: string
{
    case PENDING  = 'pending';
    case ACHIEVED = 'achieved';
    case MISSED   = 'missed';
}
