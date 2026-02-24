<?php

namespace Modules\QualityControl\Domain\Enums;

enum AlertPriority: string
{
    case Low    = 'low';
    case Medium = 'medium';
    case High   = 'high';
    case Critical = 'critical';
}
