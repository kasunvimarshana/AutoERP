<?php

declare(strict_types=1);

namespace Modules\HR\Domain\ValueObjects;

enum ShiftType: string
{
    case REGULAR = 'regular';
    case SPLIT = 'split';
    case FLEXIBLE = 'flexible';
    case NIGHT = 'night';
    case ROTATING = 'rotating';
}
