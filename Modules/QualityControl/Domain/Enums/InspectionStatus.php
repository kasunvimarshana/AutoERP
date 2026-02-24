<?php

namespace Modules\QualityControl\Domain\Enums;

enum InspectionStatus: string
{
    case Draft     = 'draft';
    case InProgress = 'in_progress';
    case Passed    = 'passed';
    case Failed    = 'failed';
    case Cancelled = 'cancelled';
}
